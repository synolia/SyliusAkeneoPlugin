<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Product\Factory\ProductFactory;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Sylius\Component\Product\Generator\SlugGeneratorInterface;
use Sylius\Component\Product\Model\ProductAssociationInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Product\Repository\ProductAssociationTypeRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules;
use Synolia\SyliusAkeneoPlugin\Event\Product\AfterProcessingProductEvent;
use Synolia\SyliusAkeneoPlugin\Event\Product\BeforeProcessingProductEvent;
use Synolia\SyliusAkeneoPlugin\Event\ProductVariant\AfterProcessingProductVariantEvent;
use Synolia\SyliusAkeneoPlugin\Event\ProductVariant\BeforeProcessingProductVariantEvent;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationException;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoProductFiltersConfigurationException;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductCategoriesPayload;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductMediaPayload;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductResourcePayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeDataProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Repository\ChannelRepository;
use Synolia\SyliusAkeneoPlugin\Repository\LocaleRepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Repository\ProductFiltersRulesRepository;
use Synolia\SyliusAkeneoPlugin\Service\ProductChannelEnabler;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;

final class SimpleProductTask extends AbstractCreateProductEntities
{
    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productFactory;

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider */
    private $taskProvider;

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

    /** @var \Synolia\SyliusAkeneoPlugin\Repository\ProductFiltersRulesRepository */
    private $productFiltersRulesRepository;

    /** @var \Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider */
    private $syliusAkeneoLocaleCodeProvider;

    /** @var AkeneoAttributeDataProviderInterface */
    private $akeneoAttributeDataProvider;

    /** @var ProductConfiguration */
    private $productConfiguration;

    /** @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface */
    private $dispatcher;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productAssociationFactory;

    /** @var \Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository */
    private $productAssociationRepository;

    /** @var \Sylius\Component\Product\Repository\ProductAssociationTypeRepositoryInterface */
    private $productAssociationTypeRepository;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        RepositoryInterface $productRepository,
        ChannelRepository $channelRepository,
        RepositoryInterface $productVariantRepository,
        RepositoryInterface $channelPricingRepository,
        LocaleRepositoryInterface $localeRepository,
        RepositoryInterface $productConfigurationRepository,
        FactoryInterface $productFactory,
        ProductVariantFactoryInterface $productVariantFactory,
        FactoryInterface $channelPricingFactory,
        EntityManagerInterface $entityManager,
        AkeneoTaskProvider $taskProvider,
        LoggerInterface $akeneoLogger,
        ProductFiltersRulesRepository $productFiltersRulesRepository,
        RepositoryInterface $productTranslationRepository,
        FactoryInterface $productTranslationFactory,
        SlugGeneratorInterface $productSlugGenerator,
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        AkeneoAttributeDataProviderInterface $akeneoAttributeDataProvider,
        EventDispatcherInterface $dispatcher,
        ProductChannelEnabler $productChannelEnabler,
        FactoryInterface $productAssociationFactory,
        EntityRepository $productAssociationRepository,
        ProductAssociationTypeRepositoryInterface $productAssociationTypeRepository
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
            $akeneoLogger,
            $productChannelEnabler
        );

        $this->productFactory = $productFactory;
        $this->taskProvider = $taskProvider;
        $this->productFiltersRulesRepository = $productFiltersRulesRepository;
        $this->productTranslationRepository = $productTranslationRepository;
        $this->productTranslationFactory = $productTranslationFactory;
        $this->productSlugGenerator = $productSlugGenerator;
        $this->syliusAkeneoLocaleCodeProvider = $syliusAkeneoLocaleCodeProvider;
        $this->akeneoAttributeDataProvider = $akeneoAttributeDataProvider;
        $this->dispatcher = $dispatcher;
        $this->productAssociationFactory = $productAssociationFactory;
        $this->productAssociationRepository = $productAssociationRepository;
        $this->productAssociationTypeRepository = $productAssociationTypeRepository;
    }

    /**
     * @param ProductPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload, array $resource): void
    {
        $this->productConfiguration = $this->productConfigurationRepository->findOneBy([]);
        /** @var \Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules $filters */
        $filters = $this->productFiltersRulesRepository->findOneBy([]);
        if (!$filters instanceof ProductFiltersRules) {
            throw new NoProductFiltersConfigurationException('Product filters must be configured before importing product attributes.');
        }
        $this->scope = $filters->getChannel();
        $this->payload = $payload;

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
                return;
            }

            $this->updateImages($payload, $resource, $product);
            $this->setProductPrices($productVariant, $resource['values']);
            $this->productChannelEnabler->enableChannelForProduct($product, $resource);

            $this->dispatcher->dispatch(new AfterProcessingProductEvent($resource, $product));
            $this->dispatcher->dispatch(new AfterProcessingProductVariantEvent($resource, $productVariant));
            $this->createAssociationForEachAssociations($resource);

            $this->entityManager->flush();
        } catch (\Throwable $throwable) {
            $this->logger->warning($throwable->getMessage());
        }
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

            return $product;
        }

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

    private function createAssociationForEachAssociations(array $resource): void
    {
        $product = $this->productRepository->findOneBy(['code' => $resource['identifier']]);
        if (!$product instanceof ProductInterface) {
            $this->logger->critical(sprintf('Product %s not found in database, association skip.', $resource['identifier']));

            return;
        }

        foreach ($resource['associations'] as $associationTypeCode => $associations) {
            $this->associateProducts($product, $associationTypeCode, $associations);
        }

        $this->entityManager->flush();
    }

    private function associateProducts(
        ProductInterface $product,
        string $associationTypeCode,
        array $associations
    ): void {
        $productAssociationType = $this->productAssociationTypeRepository->findOneBy(['code' => $associationTypeCode]);
        if (!$productAssociationType instanceof ProductAssociationTypeInterface) {
            $this->logger->warning(sprintf('Product association type %s not found.', $associationTypeCode));

            return;
        }

        $productAssociation = $this->productAssociationRepository->findOneBy(
            [
                'owner' => $product,
                'type' => $productAssociationType
            ]
        );

        if (!$productAssociation instanceof ProductAssociationInterface) {
            $productAssociation = $this->productAssociationFactory->createNew();
            if (!$productAssociation instanceof ProductAssociationInterface) {
                throw new \LogicException('Not an instance of productAssociation.');
            }
        }

        $productAssociation->setOwner($product);
        $productAssociation->setType($productAssociationType);

        foreach ($associations['products'] as $association) {
            $this->associateProduct($productAssociation, $association);
        }

        $this->entityManager->persist($productAssociation);
    }

    private function associateProduct(ProductAssociationInterface $productAssociation, string $associatedProduct): void
    {
        $product = $this->productRepository->findOneBy(['code' => $associatedProduct]);
        if (!$product instanceof ProductInterface) {
            $this->logger->warning(sprintf('Product %s not and could not be associated', $associatedProduct));

            return;
        }

        $productAssociation->addAssociatedProduct($product);
    }
}
