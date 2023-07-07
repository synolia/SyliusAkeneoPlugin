<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Family;

use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroup;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroupInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Family\FamilyPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Processor\ProductGroup\FamilyVariationAxeProcessor;
use Synolia\SyliusAkeneoPlugin\Task\AbstractBatchTask;

final class BatchFamilyTask extends AbstractBatchTask
{
    private int $groupAlreadyExistCount = 0;

    private int $groupCreateCount = 0;

    private array $productGroupsMapping;

    public function __construct(
        EntityManagerInterface $entityManager,
        private EntityRepository $productGroupRepository,
        private LoggerInterface $logger,
        private FamilyVariationAxeProcessor $familyVariationAxeProcessor,
        private FactoryInterface $productGroupFactory,
    ) {
        parent::__construct($entityManager);
    }

    /**
     * @param FamilyPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);
        $this->productGroupsMapping = [];
        $resources = [];

        $query = $this->getSelectStatement($payload);
        /** @var Result $queryResult */
        $queryResult = $query->executeQuery();

        while ($results = $queryResult->fetchAll()) {
            foreach ($results as $result) {
                try {
                    $resource = json_decode($result['values'], true, 512, \JSON_THROW_ON_ERROR);
                    $resources[] = $resource;

                    $this->createProductGroups($resource);
                    $this->removeEntry($payload, (int) $result['id']);
                } catch (\Throwable $throwable) {
                    $this->logger->warning($throwable->getMessage());
                    $this->removeEntry($payload, (int) $result['id']);
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

    private function createGroupForCodeAndFamily(
        string $code,
        string $family,
        string $familyVariant,
        ?string $parent = null,
    ): ProductGroupInterface {
        if (isset($this->productGroupsMapping[$code])) {
            return $this->productGroupsMapping[$code];
        }

        $productGroup = $this->productGroupRepository->findOneBy(['model' => $code]);
        if ($productGroup instanceof ProductGroup) {
            $this->productGroupsMapping[$code] = $productGroup;
            ++$this->groupAlreadyExistCount;

            $this->logger->info(sprintf(
                'Skipping ProductGroup "%s" for family "%s" as it already exists.',
                $code,
                $family,
            ));

            $productGroup->setParent($this->productGroupsMapping[$parent] ?? null);
            $productGroup->setModel($code);
            $productGroup->setFamily($family);
            $productGroup->setFamilyVariant($familyVariant);

            return $productGroup;
        }

        $this->logger->info(sprintf(
            'Creating ProductGroup "%s" for family "%s"',
            $code,
            $family,
        ));

        /** @var ProductGroupInterface $productGroup */
        $productGroup = $this->productGroupFactory->createNew();
        $productGroup->setParent($this->productGroupsMapping[$parent] ?? null);
        $productGroup->setModel($code);
        $productGroup->setFamily($family);
        $productGroup->setFamilyVariant($familyVariant);
        $this->entityManager->persist($productGroup);
        $this->productGroupsMapping[$code] = $productGroup;

        ++$this->groupCreateCount;

        return $productGroup;
    }

    private function createProductGroups(array $resource): void
    {
        if (null !== $resource['parent']) {
            $this->createGroupForCodeAndFamily($resource['parent'], $resource['family'], $resource['family_variant']);
        }

        if (null !== $resource['code']) {
            $this->createGroupForCodeAndFamily($resource['code'], $resource['family'], $resource['family_variant'], $resource['parent']);
        }
    }
}
