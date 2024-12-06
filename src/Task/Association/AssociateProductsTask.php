<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Association;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Product\Model\ProductAssociationInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Product\Repository\ProductAssociationTypeRepositoryInterface;
use Sylius\Component\Product\Repository\ProductRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroupInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Repository\ProductGroupRepository;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

class AssociateProductsTask implements AkeneoTaskInterface
{
    public function __construct(
        private ProductGroupRepository $productGroupRepository,
        private ProductAssociationTypeRepositoryInterface $productAssociationTypeRepository,
        private LoggerInterface $akeneoLogger,
        private ProductRepositoryInterface $productRepository,
        private EntityManagerInterface $entityManager,
        private FactoryInterface $productAssociationFactory,
        private RepositoryInterface $productAssociationRepository,
    ) {
    }

    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        /** @var ProductGroupInterface[] $productGroups */
        $productGroups = $this->productGroupRepository->findAll();

        foreach ($productGroups as $productGroup) {
            $this->akeneoLogger->debug('Processing ProductGroup', [
                'parent' => $productGroup->getModel(),
            ]);

            $parentModel = $this->productRepository->findOneBy(['code' => $productGroup->getModel()]);

            if (!$parentModel instanceof ProductInterface) {
                $this->akeneoLogger->debug('Skipped ProductGroup', [
                    'parent' => $productGroup->getModel(),
                ]);

                continue;
            }

            /**
             * @var mixed $association
             */
            foreach ($productGroup->getAssociations() as $associationType => $association) {
                if (!is_array($association)) {
                    continue;
                }

                if (!array_key_exists('product_models', $association) || [] === $association['product_models']) {
                    continue;
                }

                /** @var ProductAssociationTypeInterface $productAssociationType */
                $productAssociationType = $this->productAssociationTypeRepository->findOneBy(['code' => $associationType]);

                $this->akeneoLogger->debug('Processing ProductAssociationType', [
                    'code' => $associationType,
                    'name' => $productAssociationType->getName() ?? '',
                ]);

                /** @var ProductInterface[] $models */
                $models = $this->retrieveModels($productAssociationType, $association['product_models']);

                foreach ($models as $model) {
                    $productAssociation = $this->productAssociationRepository->findOneBy(['owner' => $parentModel, 'type' => $productAssociationType]);

                    if (!$productAssociation instanceof ProductAssociationInterface) {
                        /** @var ProductAssociationInterface $productAssociation */
                        $productAssociation = $this->productAssociationFactory->createNew();
                        $productAssociation->setType($productAssociationType);

                        $this->entityManager->persist($productAssociation);
                        $parentModel->addAssociation($productAssociation);
                    }

                    /** @var ProductInterface|null $reference */
                    $reference = $this->entityManager->getPartialReference(ProductInterface::class, $model->getId());

                    if (null === $reference) {
                        continue;
                    }

                    $productAssociation->addAssociatedProduct($reference);
                    $this->entityManager->flush();
                }
            }
        }

        return $payload;
    }

    private function retrieveModels(
        ProductAssociationTypeInterface $productAssociationType,
        array $association,
    ): array {
        $models = [];

        /** @var string $model */
        foreach ($association as $model) {
            // first we need to check if the model is an imported variation axis
            $product = $this->productRepository->findOneBy(['code' => $model]);

            if ($product instanceof ProductInterface) {
                $models[] = $product;

                continue;
            }

            // if the product is not found, search it is a top level product "common"
            // if found, we have to get all the products bound to this "parent" (second variation)
            /** @var ProductGroupInterface $associationProductGroup */
            $associationProductGroup = $this->productGroupRepository->findOneBy(['model' => $model]);

            foreach ($associationProductGroup->getProducts() as $product) {
                $models[] = $product;

                $this->akeneoLogger->debug('Added product to association group', [
                    'association_code' => $productAssociationType->getCode(),
                    'code' => $product->getCode(),
                ]);
            }
        }

        return $models;
    }
}
