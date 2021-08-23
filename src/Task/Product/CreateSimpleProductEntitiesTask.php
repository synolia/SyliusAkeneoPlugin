<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Product\Factory\ProductFactory;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Sylius\Component\Product\Generator\SlugGeneratorInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Event\Product\AfterProcessingProductEvent;
use Synolia\SyliusAkeneoPlugin\Event\Product\BeforeProcessingProductEvent;
use Synolia\SyliusAkeneoPlugin\Event\ProductVariant\AfterProcessingProductVariantEvent;
use Synolia\SyliusAkeneoPlugin\Event\ProductVariant\BeforeProcessingProductVariantEvent;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationException;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoProductFiltersConfigurationException;
use Synolia\SyliusAkeneoPlugin\Filter\ProductFilterInterface;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductCategoriesPayload;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductMediaPayload;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductResourcePayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeDataProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Repository\ChannelRepository;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class CreateSimpleProductEntitiesTask extends AbstractCreateProductEntities implements AkeneoTaskInterface
{
    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productFactory;

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider */
    private $taskProvider;

    /** @var int */
    private $updateCount = 0;

    /** @var int */
    private $createCount = 0;

    /** @var string */
    private $type;

    /** @var ProductPayload */
    private $payload;

    /** @var string */
    private $scope;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productTranslationRepository;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productTranslationFactory;

    /** @var \Sylius\Component\Product\Generator\SlugGeneratorInterface */
    private $productSlugGenerator;

    /** @var \Synolia\SyliusAkeneoPlugin\Filter\ProductFilterInterface */
    private $productFilter;

    /** @var \Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider */
    private $syliusAkeneoLocaleCodeProvider;

    /** @var AkeneoAttributeDataProviderInterface */
    private $akeneoAttributeDataProvider;

    /** @var ProductConfiguration */
    private $productConfiguration;

    /** @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface */
    private $dispatcher;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        RepositoryInterface $productRepository,
        ChannelRepository $channelRepository,
        RepositoryInterface $productVariantRepository,
        RepositoryInterface $channelPricingRepository,
        RepositoryInterface $localeRepository,
        RepositoryInterface $productConfigurationRepository,
        FactoryInterface $productFactory,
        ProductVariantFactoryInterface $productVariantFactory,
        FactoryInterface $channelPricingFactory,
        EntityManagerInterface $entityManager,
        AkeneoTaskProvider $taskProvider,
        LoggerInterface $akeneoLogger,
        ProductFilterInterface $productFilter,
        RepositoryInterface $productTranslationRepository,
        FactoryInterface $productTranslationFactory,
        SlugGeneratorInterface $productSlugGenerator,
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        AkeneoAttributeDataProviderInterface $akeneoAttributeDataProvider,
        EventDispatcherInterface $dispatcher
    ) {
        parent::__construct(
            $entityManager,
            $productVariantRepository,
            $productRepository,
            $channelRepository,
            $channelPricingRepository,
            $localeRepository,
            $productConfigurationRepository,
            $productVariantFactory,
            $channelPricingFactory,
            $akeneoLogger
        );

        $this->productFactory = $productFactory;
        $this->taskProvider = $taskProvider;
        $this->productFilter = $productFilter;
        $this->productTranslationRepository = $productTranslationRepository;
        $this->productTranslationFactory = $productTranslationFactory;
        $this->productSlugGenerator = $productSlugGenerator;
        $this->syliusAkeneoLocaleCodeProvider = $syliusAkeneoLocaleCodeProvider;
        $this->akeneoAttributeDataProvider = $akeneoAttributeDataProvider;
        $this->dispatcher = $dispatcher;
    }

    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload instanceof ProductPayload) {
            return $payload;
        }

        $this->payload = $payload;
        $this->logger->debug(self::class);
        $this->type = 'SimpleProduct';
        $this->logger->notice(Messages::createOrUpdate($this->type));
        $this->productConfiguration = $this->productConfigurationRepository->findOneBy([]);

        $scope = $this->productFilter->getChannel();
        if ($scope === null) {
            throw new NoProductFiltersConfigurationException('Product filters must be configured before importing product attributes.');
        }
        $this->scope = $scope;

        $processedCount = 0;
        $totalItemsCount = $this->countTotalProducts(true);

        $query = $this->prepareSelectQuery(true, ProductPayload::SELECT_PAGINATION_SIZE, 0);
        $query->execute();

        while ($results = $query->fetchAll()) {
            foreach ($results as $result) {
                $resource = \json_decode($result['values'], true);

                try {
                    $this->dispatcher->dispatch(new BeforeProcessingProductEvent($resource));

                    $product = $this->getOrCreateEntity($resource);

                    $this->updateProductRequirementsForActiveLocales(
                        $product,
                        $resource['family'],
                        $resource
                    );

                    $this->dispatcher->dispatch(new BeforeProcessingProductVariantEvent($resource, $product));

                    $productVariant = $this->getOrCreateSimpleVariant($product);
                    $this->linkCategoriesToProduct($payload, $product, $resource['categories']);

                    $productResourcePayload = $this->insertAttributesToProduct($payload, $product, $resource['family'], $resource);
                    if (null === $productResourcePayload->getProduct()) {
                        continue;
                    }

                    $this->updateImages($payload, $resource, $product);
                    $this->setProductPrices($productVariant, $resource['values']);

                    $this->dispatcher->dispatch(new AfterProcessingProductEvent($resource, $product));
                    $this->dispatcher->dispatch(new AfterProcessingProductVariantEvent($resource, $productVariant));

                    $this->entityManager->flush();
                } catch (\Throwable $throwable) {
                    $this->logger->warning($throwable->getMessage());
                }
            }

            $processedCount += \count($results);
            $this->logger->info(\sprintf('Processed %d products out of %d.', $processedCount, $totalItemsCount));
            $query = $this->prepareSelectQuery(true, ProductPayload::SELECT_PAGINATION_SIZE, $processedCount);
            $query->execute();
        }

        $this->logger->notice(Messages::countCreateAndUpdate($this->type, $this->createCount, $this->updateCount));

        return $payload;
    }

    private function getOrCreateEntity(array $resource): ProductInterface
    {
        /** @var ProductInterface $product */
        $product = $this->productRepository->findOneBy(['code' => $resource['identifier']]);

        if (!$product instanceof ProductInterface) {
            if (!$this->productFactory instanceof ProductFactory) {
                throw new \LogicException('Wrong Factory');
            }

            if (null === $resource['parent']) {
                /** @var ProductInterface $product */
                $product = $this->productFactory->createNew();
            }

            $product->setCode($resource['identifier']);
            $this->entityManager->persist($product);

            ++$this->createCount;
            $this->logger->info(Messages::hasBeenCreated($this->type, (string) $product->getCode()));

            return $product;
        }

        ++$this->updateCount;
        $this->logger->info(Messages::hasBeenUpdated($this->type, (string) $product->getCode()));

        return $product;
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @todo Need refacto
     */
    private function updateProductRequirementsForActiveLocales(
        ProductInterface $product,
        string $familyCode,
        array $resource
    ): void {
        $missingNameTranslationCount = 0;
        $familyResource = $this->payload->getAkeneoPimClient()->getFamilyApi()->get($familyCode);
        foreach ($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms() as $usedLocalesOnBothPlatform) {
            $productName = null;

            if (isset($resource['values'][$familyResource['attribute_as_label']])) {
                try {
                    $productName = $this->akeneoAttributeDataProvider->getData(
                        $familyResource['attribute_as_label'],
                        $resource['values'][$familyResource['attribute_as_label']],
                        $usedLocalesOnBothPlatform,
                        $this->scope
                    );
                } catch (MissingLocaleTranslationException $exception) {
                    $this->logger->notice(sprintf('Missing locale for field %s.', $familyResource['attribute_as_label']));

                    continue;
                }
            }

            if (null === $productName) {
                $productName = \sprintf('[%s]', $product->getCode());
                ++$missingNameTranslationCount;
            }

            $productTranslation = $this->productTranslationRepository->findOneBy([
                'translatable' => $product,
                'locale' => $usedLocalesOnBothPlatform,
            ]);

            if (!$productTranslation instanceof ProductTranslationInterface) {
                /** @var ProductTranslationInterface $productTranslation */
                $productTranslation = $this->productTranslationFactory->createNew();
                $productTranslation->setLocale($usedLocalesOnBothPlatform);
                $product->addTranslation($productTranslation);
            }

            $productTranslation->setName($productName);

            if (null !== $product->getId() &&
                null !== $productTranslation->getSlug() &&
                null !== $this->productConfiguration &&
                false === $this->productConfiguration->getRegenerateUrlRewrites()) {
                // no regenerate slug if config disable it

                continue;
            }

            if ($missingNameTranslationCount > 0) {
                //Multiple product has the same name
                $productTranslation->setSlug(\sprintf(
                    '%s-%s-%d',
                    $resource['code'] ?? $resource['identifier'],
                    $this->productSlugGenerator->generate($productName),
                    $missingNameTranslationCount
                ));

                continue;
            }

            //Multiple product has the same name
            $productTranslation->setSlug(\sprintf(
                '%s-%s',
                $resource['code'] ?? $resource['identifier'],
                $this->productSlugGenerator->generate($productName)
            ));
        }
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload $payload
     */
    private function linkCategoriesToProduct(PipelinePayloadInterface $payload, ProductInterface $product, array $categories): void
    {
        $productCategoriesPayload = new ProductCategoriesPayload($payload->getAkeneoPimClient());
        $productCategoriesPayload
            ->setProduct($product)
            ->setCategories($categories)
        ;
        $addProductCategoriesTask = $this->taskProvider->get(AddProductToCategoriesTask::class);
        $addProductCategoriesTask->__invoke($productCategoriesPayload);
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload $payload
     */
    private function insertAttributesToProduct(
        PipelinePayloadInterface $payload,
        ProductInterface $product,
        string $familyCode,
        array $resource
    ): ProductResourcePayload {
        $familyResource = $this->payload->getAkeneoPimClient()->getFamilyApi()->get($familyCode);

        $productResourcePayload = new ProductResourcePayload($payload->getAkeneoPimClient());
        $productResourcePayload
            ->setProduct($product)
            ->setResource($resource)
            ->setFamily($familyResource)
            ->setScope($this->scope)
        ;
        $addAttributesToProductTask = $this->taskProvider->get(AddAttributesToProductTask::class);
        $addAttributesToProductTask->__invoke($productResourcePayload);

        return $productResourcePayload;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload $payload
     */
    private function updateImages(PipelinePayloadInterface $payload, array $resource, ProductInterface $product): void
    {
        $productMediaPayload = new ProductMediaPayload($payload->getAkeneoPimClient());
        $productMediaPayload
            ->setProduct($product)
            ->setAttributes($resource['values'])
            ->setProductConfiguration($this->productConfiguration)
        ;
        $imageTask = $this->taskProvider->get(InsertProductImagesTask::class);
        $imageTask->__invoke($productMediaPayload);
    }
}
