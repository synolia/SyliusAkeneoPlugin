<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Family;

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
    private EntityRepository $productGroupRepository;

    private LoggerInterface $logger;

    private int $groupAlreadyExistCount = 0;

    private int $groupCreateCount = 0;

    private array $productGroupsMapping;

    private FamilyVariationAxeProcessor $familyVariationAxeProcessor;

    private FactoryInterface $productGroupFactory;

    public function __construct(
        EntityManagerInterface $entityManager,
        EntityRepository $productGroupRepository,
        LoggerInterface $akeneoLogger,
        FamilyVariationAxeProcessor $familyVariationAxeProcessor,
        FactoryInterface $productGroupFactory
    ) {
        parent::__construct($entityManager);

        $this->productGroupRepository = $productGroupRepository;
        $this->logger = $akeneoLogger;
        $this->familyVariationAxeProcessor = $familyVariationAxeProcessor;
        $this->productGroupFactory = $productGroupFactory;
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
        $query->executeStatement();

        while ($results = $query->fetchAll()) {
            foreach ($results as $result) {
                try {
                    $resource = json_decode($result['values'], true);
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

    private function createGroupForCodeAndFamily(string $code, string $family): ProductGroupInterface
    {
        if (isset($this->productGroupsMapping[$code])) {
            return $this->productGroupsMapping[$code];
        }

        $productGroup = $this->productGroupRepository->findOneBy(['productParent' => $code]);
        if ($productGroup instanceof ProductGroup) {
            ++$this->groupAlreadyExistCount;

            $this->logger->info(sprintf(
                'Skipping ProductGroup "%s" for family "%s" as it already exists.',
                $code,
                $family,
            ));

            $productGroup->cleanProducts();

            return $productGroup;
        }

        $this->logger->info(sprintf(
            'Creating ProductGroup "%s" for family "%s"',
            $code,
            $family,
        ));

        /** @var ProductGroupInterface $productGroup */
        $productGroup = $this->productGroupFactory->createNew();
        $productGroup->setProductParent($code);
        $productGroup->setFamily($family);
        $this->entityManager->persist($productGroup);
        $this->productGroupsMapping[$code] = $productGroup;

        ++$this->groupCreateCount;

        return $productGroup;
    }

    private function createProductGroups(array $resource): void
    {
        if (null !== $resource['parent']) {
            $this->createGroupForCodeAndFamily($resource['parent'], $resource['family']);
        }
        if (null !== $resource['code']) {
            $this->createGroupForCodeAndFamily($resource['code'], $resource['family']);
        }
    }
}
