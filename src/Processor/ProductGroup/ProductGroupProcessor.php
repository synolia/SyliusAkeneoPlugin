<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductGroup;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroupInterface;

class ProductGroupProcessor
{
    private array $productGroupsMapping;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private FamilyVariationAxeProcessor $familyVariationAxeProcessor,
        private EntityRepository $productGroupRepository,
        private FactoryInterface $productGroupFactory,
    ) {
    }

    public function process(array $resource): void
    {
        $this->createProductGroups($resource);
        $this->familyVariationAxeProcessor->process($resource);
    }

    private function createGroupForCodeAndFamily(
        string $code,
        string $family,
        string $familyVariant,
        ?string $parent = null,
    ): void {
        if (isset($this->productGroupsMapping[$code])) {
            return;
        }

        $productGroup = $this->productGroupRepository->findOneBy(['model' => $code]);

        if ($productGroup instanceof ProductGroupInterface) {
            $this->productGroupsMapping[$code] = $productGroup;

            $this->logger->debug(sprintf(
                'Skipping ProductGroup "%s" for family "%s" as it already exists.',
                $code,
                $family,
            ));

            $productGroup->setParent($this->productGroupsMapping[$parent] ?? null);
            $productGroup->setModel($code);
            $productGroup->setFamily($family);
            $productGroup->setFamilyVariant($familyVariant);

            return;
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
