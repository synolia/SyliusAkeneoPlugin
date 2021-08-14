<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Family;

use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroup;
use Synolia\SyliusAkeneoPlugin\Payload\Family\FamilyPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Processor\ProductGroup\FamilyVariationAxeProcessor;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Task\BatchTaskInterface;

final class BatchFamilyTask implements AkeneoTaskInterface, BatchTaskInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var EntityRepository */
    private $productGroupRepository;

    /** @var LoggerInterface */
    private $logger;

    /** @var int */
    private $groupAlreadyExistCount = 0;

    /** @var int */
    private $groupCreateCount = 0;

    /** @var array */
    private $productGroupsMapping;

    /** @var \Synolia\SyliusAkeneoPlugin\Processor\ProductGroup\FamilyVariationAxeProcessor */
    private $familyVariationAxeProcessor;

    public function __construct(
        EntityManagerInterface $entityManager,
        EntityRepository $productGroupRepository,
        LoggerInterface $akeneoLogger,
        FamilyVariationAxeProcessor $familyVariationAxeProcessor
    ) {
        $this->entityManager = $entityManager;
        $this->productGroupRepository = $productGroupRepository;
        $this->logger = $akeneoLogger;
        $this->familyVariationAxeProcessor = $familyVariationAxeProcessor;
    }

    /**
     * @param FamilyPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);
        $this->productGroupsMapping = [];
        $query = $this->entityManager->getConnection()->prepare(\sprintf(
            'SELECT id, `values`
             FROM `%s`
             WHERE id IN (%s)
             ORDER BY id ASC',
            FamilyPayload::TEMP_AKENEO_TABLE_NAME,
            implode(',', $payload->getIds())
        ));

        $query->executeStatement();

        $resources = [];

        while ($results = $query->fetchAll()) {
            foreach ($results as $result) {
                try {
                    $resource = \json_decode($result['values'], true);
                    $resources[] = $resource;

                    $this->createProductGroups($resource);
                    $deleteQuery = $this->entityManager->getConnection()->prepare(\sprintf(
                        'DELETE FROM `%s` WHERE id = :id',
                        FamilyPayload::TEMP_AKENEO_TABLE_NAME,
                    ));
                    $deleteQuery->bindValue('id', $result['id'], ParameterType::INTEGER);
                    $deleteQuery->execute();
                } catch (\Throwable $throwable) {
                    $this->logger->warning($throwable->getMessage());

                    $deleteQuery = $this->entityManager->getConnection()->prepare(\sprintf(
                        'DELETE FROM `%s` WHERE id = :id',
                        FamilyPayload::TEMP_AKENEO_TABLE_NAME,
                    ));
                    $deleteQuery->bindValue('id', $result['id'], ParameterType::INTEGER);
                    $deleteQuery->execute();
                }
            }
        }
        $this->entityManager->flush();

        foreach ($resources as $resource) {
            $this->familyVariationAxeProcessor->process($resource);
        }
        $this->entityManager->flush();

        return $payload;
    }

    private function createGroupForCodeAndFamily(string $code, string $family): ProductGroup
    {
        if (isset($this->productGroupsMapping[$code])) {
            return $this->productGroupsMapping[$code];
        }

        $productGroup = $this->productGroupRepository->findOneBy(['productParent' => $code]);
        if ($productGroup instanceof ProductGroup) {
            ++$this->groupAlreadyExistCount;

            $this->logger->info(\sprintf(
                'Skipping ProductGroup "%s" for family "%s" as it already exists.',
                $code,
                $family,
            ));

            return $productGroup;
        }

        $this->logger->info(\sprintf(
            'Creating ProductGroup "%s" for family "%s"',
            $code,
            $family,
        ));

        $productGroup = new ProductGroup();
        $productGroup->setProductParent($code);
        $productGroup->setFamily($family);
        $this->entityManager->persist($productGroup);
        $this->productGroupsMapping[$code] = $productGroup;

        ++$this->groupCreateCount;

        return $productGroup;
    }

    private function createProductGroups(array $resource): void
    {
        if ($resource['parent'] !== null) {
            $this->createGroupForCodeAndFamily($resource['parent'], $resource['family']);
        }
        if ($resource['code'] !== null) {
            $this->createGroupForCodeAndFamily($resource['code'], $resource['family']);
        }
    }
}
