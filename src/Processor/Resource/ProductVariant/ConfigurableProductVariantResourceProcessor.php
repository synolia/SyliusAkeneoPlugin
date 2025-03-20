<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Resource\ProductVariant;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Config\AkeneoAxesEnum;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroup;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroupInterface;
use Synolia\SyliusAkeneoPlugin\Event\ProductVariant\AfterProcessingProductVariantEvent;
use Synolia\SyliusAkeneoPlugin\Event\ProductVariant\BeforeProcessingProductVariantEvent;
use Synolia\SyliusAkeneoPlugin\Processor\ProductVariant\ProductVariantProcessorChainInterface;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\AkeneoResourceProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;
use Synolia\SyliusAkeneoPlugin\Repository\ProductGroupRepository;
use Synolia\SyliusAkeneoPlugin\Retriever\FamilyVariantRetrieverInterface;
use Throwable;

class ConfigurableProductVariantResourceProcessor implements AkeneoResourceProcessorInterface, ProductVariantAkeneoResourceProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RepositoryInterface $productVariantRepository,
        private RepositoryInterface $productRepository,
        private ProductGroupRepository $productGroupRepository,
        private ProductVariantFactoryInterface $productVariantFactory,
        private LoggerInterface $akeneoLogger,
        private EventDispatcherInterface $dispatcher,
        private ProductVariantProcessorChainInterface $productVariantProcessorChain,
        private ApiConnectionProviderInterface $apiConnectionProvider,
        private FamilyVariantRetrieverInterface $familyVariantRetriever,
    ) {
    }

    private function getModelCode(ProductGroupInterface $productGroup): string
    {
        if (
            $this->apiConnectionProvider->get()->getAxeAsModel() === AkeneoAxesEnum::COMMON &&
            $productGroup->getParent() !== null
        ) {
            return $productGroup->getParent()->getModel();
        }

        return $productGroup->getModel();
    }

    private function getOrCreateEntity(string $variantCode, ProductInterface $productModel): ProductVariantInterface
    {
        $productVariant = $this->productVariantRepository->findOneBy(['code' => $variantCode]);

        if (!$productVariant instanceof ProductVariantInterface) {
            /** @var ProductVariantInterface $productVariant */
            $productVariant = $this->productVariantFactory->createForProduct($productModel);
            $productVariant->setCode($variantCode);

            $this->entityManager->persist($productVariant);

            return $productVariant;
        }

        return $productVariant;
    }

    public function support(array $resource): bool
    {
        return null !== $resource['parent'];
    }

    public function process(array $resource): void
    {
        try {
            $productGroup = $this->productGroupRepository->findOneBy(['model' => $resource['parent']]);

            if (!$productGroup instanceof ProductGroup) {
                $this->akeneoLogger->warning(sprintf(
                    'Skipped product "%s" because model "%s" does not exist as group.',
                    $resource['identifier'],
                    $resource['parent'],
                ));

                return;
            }

            $this->akeneoLogger->info(sprintf(
                'Processing product "%s" on model "%s".',
                $resource['identifier'],
                $resource['parent'],
            ));

            /** @var ProductInterface|null $productModel */
            $productModel = $this->productRepository->findOneBy(['code' => $this->getModelCode($productGroup)]);

            //Skip product variant import if it does not have a parent model on Sylius
            if (!$productModel instanceof ProductInterface || !\is_string($productModel->getCode())) {
                $this->akeneoLogger->warning(sprintf(
                    'Skipped product "%s" because model "%s" does not exist.',
                    $resource['identifier'],
                    $resource['parent'],
                ));

                return;
            }

            $this->dispatcher->dispatch(new BeforeProcessingProductVariantEvent($resource, $productModel));

            $familyVariantPayload = $this->familyVariantRetriever->getVariant($resource['family'], $productGroup->getFamilyVariant());

            if (0 === (is_countable($familyVariantPayload['variant_attribute_sets']) ? \count($familyVariantPayload['variant_attribute_sets']) : 0)) {
                $this->akeneoLogger->warning(sprintf(
                    'Skipped product "%s" because group has no variation axis.',
                    $resource['identifier'],
                ));

                return;
            }

            $productVariant = $this->getOrCreateEntity($resource['identifier'], $productModel);
            $this->productVariantProcessorChain->chain($productVariant, $resource);

            $this->dispatcher->dispatch(new AfterProcessingProductVariantEvent($resource, $productVariant));
        } catch (Throwable $throwable) {
            $this->akeneoLogger->warning($throwable->getMessage(), [
                'exception' => $throwable,
            ]);
        }
    }
}
