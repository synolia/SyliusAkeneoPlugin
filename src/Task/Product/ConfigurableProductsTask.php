<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Config\AkeneoAxesEnum;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroup;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroupInterface;
use Synolia\SyliusAkeneoPlugin\Event\ProductVariant\AfterProcessingProductVariantEvent;
use Synolia\SyliusAkeneoPlugin\Event\ProductVariant\AfterProductVariantRetrievedEvent;
use Synolia\SyliusAkeneoPlugin\Event\ProductVariant\BeforeProcessingProductVariantEvent;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Processor\Product\ProductChannelEnablerProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Processor\ProductVariant\ProductVariantProcessorChainInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;
use Synolia\SyliusAkeneoPlugin\Repository\ChannelRepository;
use Synolia\SyliusAkeneoPlugin\Repository\LocaleRepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Repository\ProductGroupRepository;
use Throwable;

final class ConfigurableProductsTask extends AbstractCreateProductEntities
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        RepositoryInterface $productVariantRepository,
        RepositoryInterface $productRepository,
        ChannelRepository $channelRepository,
        LocaleRepositoryInterface $localeRepository,
        RepositoryInterface $productConfigurationRepository,
        private ProductGroupRepository $productGroupRepository,
        ProductVariantFactoryInterface $productVariantFactory,
        LoggerInterface $akeneoLogger,
        private EventDispatcherInterface $dispatcher,
        ProductChannelEnablerProcessorInterface $productChannelEnabler,
        private ProductVariantProcessorChainInterface $productVariantProcessorChain,
        private ClientFactoryInterface $clientFactory,
        private ApiConnectionProviderInterface $apiConnectionProvider,
    ) {
        parent::__construct(
            $entityManager,
            $productVariantRepository,
            $productRepository,
            $channelRepository,
            $localeRepository,
            $productConfigurationRepository,
            $productVariantFactory,
            $akeneoLogger,
            $productChannelEnabler,
        );
    }

    /**
     * @param ProductPayload $payload
     *
     * @inheritDoc
     */
    public function __invoke(PipelinePayloadInterface $payload, array $resource): void
    {
        try {
            $productGroup = $this->productGroupRepository->findOneBy(['model' => $resource['parent']]);

            if (!$productGroup instanceof ProductGroup) {
                $this->logger->warning(sprintf(
                    'Skipped product "%s" because model "%s" does not exist as group.',
                    $resource['identifier'],
                    $resource['parent'],
                ));

                return;
            }

            $this->logger->info(sprintf(
                'Processing product "%s" on model "%s".',
                $resource['identifier'],
                $resource['parent'],
            ));

            /** @var ProductInterface|null $productModel */
            $productModel = $this->productRepository->findOneBy(['code' => $this->getModelCode($productGroup)]);

            //Skip product variant import if it does not have a parent model on Sylius
            if (!$productModel instanceof ProductInterface || !\is_string($productModel->getCode())) {
                $this->logger->warning(sprintf(
                    'Skipped product "%s" because model "%s" does not exist.',
                    $resource['identifier'],
                    $resource['parent'],
                ));

                return;
            }

            $this->dispatcher->dispatch(new BeforeProcessingProductVariantEvent($resource, $productModel));

            $familyVariantPayload = $this->clientFactory
                ->createFromApiCredentials()
                ->getFamilyVariantApi()
                ->get(
                    $resource['family'],
                    $productGroup->getFamilyVariant(),
                )
            ;

            if (0 === (is_countable($familyVariantPayload['variant_attribute_sets']) ? \count($familyVariantPayload['variant_attribute_sets']) : 0)) {
                $this->logger->warning(sprintf(
                    'Skipped product "%s" because group has no variation axis.',
                    $resource['identifier'],
                ));

                return;
            }

            $productVariant = $this->getOrCreateEntity($resource['identifier'], $productModel);
            $this->dispatcher->dispatch(new AfterProductVariantRetrievedEvent($resource, $productVariant));
            $event = new AfterProcessingProductVariantEvent($resource, clone $productVariant, $productVariant);
            $this->productVariantProcessorChain->chain($productVariant, $resource);
            $this->dispatcher->dispatch($event);

            $this->entityManager->flush();
        } catch (Throwable $throwable) {
            $this->logger->warning($throwable->getMessage(), [
                'exception' => $throwable,
            ]);
        }
    }

    private function getModelCode(ProductGroupInterface $productGroup): string
    {
        if ($this->apiConnectionProvider->get()->getAxeAsModel() === AkeneoAxesEnum::COMMON &&
            $productGroup->getParent() !== null) {
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
}
