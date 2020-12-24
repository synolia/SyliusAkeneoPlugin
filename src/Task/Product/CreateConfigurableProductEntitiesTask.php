<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslationInterface;
use Sylius\Component\Product\Model\ProductVariantTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroup;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoProductFiltersConfigurationException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Processor\MissingAkeneoAttributeProcessorException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Manager\ProductOptionManager;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductVariantMediaPayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeProcessorProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Repository\ChannelRepository;
use Synolia\SyliusAkeneoPlugin\Repository\ProductFiltersRulesRepository;
use Synolia\SyliusAkeneoPlugin\Repository\ProductGroupRepository;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Task\AttributeOption\CreateUpdateDeleteTask;

final class CreateConfigurableProductEntitiesTask extends AbstractCreateProductEntities implements AkeneoTaskInterface
{
    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productOptionRepository;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productOptionValueRepository;

    /** @var \Synolia\SyliusAkeneoPlugin\Repository\ProductGroupRepository */
    private $productGroupRepository;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productOptionValueTranslationRepository;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productVariantTranslationRepository;

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider */
    private $taskProvider;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productOptionValueFactory;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productVariantTranslationFactory;

    /** @var int */
    private $updateCount = 0;

    /** @var int */
    private $createCount = 0;

    /** @var string */
    private $type;

    /** @var \Synolia\SyliusAkeneoPlugin\Repository\ProductFiltersRulesRepository */
    private $productFiltersRulesRepository;

    /** @var string */
    private $scope;

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeProcessorProviderInterface */
    private $akeneoAttributeProcessorProvider;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        RepositoryInterface $productVariantRepository,
        RepositoryInterface $productRepository,
        RepositoryInterface $productOptionRepository,
        RepositoryInterface $productOptionValueRepository,
        RepositoryInterface $productOptionValueTranslationRepository,
        ChannelRepository $channelRepository,
        RepositoryInterface $channelPricingRepository,
        RepositoryInterface $localeRepository,
        RepositoryInterface $productConfigurationRepository,
        ProductGroupRepository $productGroupRepository,
        ProductVariantFactoryInterface $productVariantFactory,
        FactoryInterface $channelPricingFactory,
        AkeneoTaskProvider $taskProvider,
        LoggerInterface $akeneoLogger,
        FactoryInterface $productOptionValueFactory,
        RepositoryInterface $productVariantTranslationRepository,
        FactoryInterface $productVariantTranslationFactory,
        ProductFiltersRulesRepository $productFiltersRulesRepository,
        AkeneoAttributeProcessorProviderInterface $akeneoAttributeProcessorProvider
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

        $this->productOptionRepository = $productOptionRepository;
        $this->productOptionValueRepository = $productOptionValueRepository;
        $this->productOptionValueTranslationRepository = $productOptionValueTranslationRepository;
        $this->productGroupRepository = $productGroupRepository;
        $this->taskProvider = $taskProvider;
        $this->productOptionValueFactory = $productOptionValueFactory;
        $this->productVariantTranslationRepository = $productVariantTranslationRepository;
        $this->productVariantTranslationFactory = $productVariantTranslationFactory;
        $this->productFiltersRulesRepository = $productFiltersRulesRepository;
        $this->akeneoAttributeProcessorProvider = $akeneoAttributeProcessorProvider;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload instanceof ProductPayload) {
            return $payload;
        }
        $this->logger->debug(self::class);
        $this->type = 'ConfigurableProduct';
        $this->logger->notice(Messages::createOrUpdate($this->type));
        /** @var \Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules $filters */
        $filters = $this->productFiltersRulesRepository->findOneBy([]);
        if (!$filters instanceof ProductFiltersRules) {
            throw new NoProductFiltersConfigurationException('Product filters must be configured before importing product attributes.');
        }
        $this->scope = $filters->getChannel();

        $processedCount = 0;
        $totalItemsCount = $this->countTotalProducts(false);

        $query = $this->prepareSelectQuery(false, ProductPayload::SELECT_PAGINATION_SIZE, 0);
        $query->execute();

        while ($results = $query->fetchAll()) {
            foreach ($results as $result) {
                $resource = \json_decode($result['values'], true);

                try {
                    /** @var ProductInterface $productModel */
                    $productModel = $this->productRepository->findOneBy(['code' => $resource['parent']]);

                    //Skip product variant import if it does not have a parent model on Sylius
                    if (!$productModel instanceof ProductInterface || !is_string($productModel->getCode())) {
                        $this->logger->warning(\sprintf(
                            'Skipped product "%s" because model "%s" does not exist.',
                            $resource['identifier'],
                            $resource['parent'],
                        ));

                        continue;
                    }

                    $productGroup = $this->productGroupRepository->findOneBy(['productParent' => $productModel->getCode()]);

                    if (!$productGroup instanceof ProductGroup) {
                        $this->logger->warning(\sprintf(
                            'Skipped product "%s" because model "%s" does not exist as group.',
                            $resource['identifier'],
                            $resource['parent'],
                        ));

                        continue;
                    }

                    $variationAxes = $productGroup->getVariationAxes();

                    if (\count($variationAxes) === 0) {
                        $this->logger->warning(\sprintf(
                            'Skipped product "%s" because group has no variation axis.',
                            $resource['identifier'],
                        ));

                        continue;
                    }

                    $this->processVariations($payload, $resource['identifier'], $productModel, $resource['values'], $variationAxes);
                    $this->entityManager->flush();
                } catch (\Throwable $throwable) {
                    $this->logger->warning($throwable->getMessage());
                }
            }

            $processedCount += \count($results);
            $this->logger->info(\sprintf('Processed %d products out of %d.', $processedCount, $totalItemsCount));
            $query = $this->prepareSelectQuery(false, ProductPayload::SELECT_PAGINATION_SIZE, $processedCount);
            $query->execute();
        }

        $this->logger->notice(Messages::countCreateAndUpdate($this->type, $this->createCount, $this->updateCount));

        return $payload;
    }

    private function processVariations(
        ProductPayload $payload,
        string $variantCode,
        ProductInterface $productModel,
        array $attributes,
        array $variationAxes
    ): void {
        $productVariant = $this->getOrCreateEntity($variantCode, $productModel);
        foreach ($attributes as $attributeCode => $values) {
            try {
                $processor = $this->akeneoAttributeProcessorProvider->getProcessor($attributeCode, [
                    'calledBy' => $this,
                    'model' => $productVariant,
                    'scope' => $this->scope,
                    'data' => $values,
                ]);
                $processor->process($attributeCode, [
                    'calledBy' => $this,
                    'model' => $productVariant,
                    'scope' => $this->scope,
                    'data' => $values,
                ]);
            } catch (MissingAkeneoAttributeProcessorException $missingAkeneoAttributeProcessorException) {
                $this->logger->debug($missingAkeneoAttributeProcessorException->getMessage());
            }

            /*
             * Skip attributes that aren't variation axes.
             * Variation axes value will be created as option for the product
             */
            if (!\in_array($attributeCode, $variationAxes, true)) {
                continue;
            }

            /** @var ProductOptionInterface $productOption */
            $productOption = $this->productOptionRepository->findOneBy(['code' => $attributeCode]);

            //We cannot create the variant if the option does not exist
            if (!$productOption instanceof ProductOptionInterface) {
                $this->logger->warning(\sprintf(
                    'Skipped ProductVariant "%s" creation because ProductOption "%s" does not exist.',
                    $variantCode,
                    $attributeCode
                ));

                continue;
            }

            if ($productModel->hasOption($productOption)) {
                $productModel->addOption($productOption);
            }

            $this->setProductOptionValues($productVariant, $productOption, $values);
            $this->updateImages($payload, $attributes, $productVariant);
            $this->setProductPrices($productVariant, $attributes);
        }
    }

    private function getLocales(): iterable
    {
        /** @var LocaleInterface[] $locales */
        $locales = $this->localeRepository->findAll();

        foreach ($locales as $locale) {
            yield $locale->getCode();
        }
    }

    private function setProductOptionValues(
        ProductVariantInterface $productVariant,
        ProductOptionInterface $productOption,
        array $values
    ): void {
        foreach ($values as $optionValue) {
            $code = $this->getCode($productOption, $optionValue['data']);
            $value = $this->getValue($optionValue['data']);

            $productOptionValue = $this->productOptionValueRepository->findOneBy([
                'option' => $productOption,
                'code' => $code,
            ]);

            if (!$productOptionValue instanceof ProductOptionValueInterface) {
                $this->logger->warning(\sprintf(
                    'Skipped variant value %s for option %s on variant %s because ProductOptionValue does not exist.',
                    $value,
                    $productOption->getCode(),
                    $productVariant->getCode(),
                ));

                return;
            }

            //Product variant already have this value
            if (!$productVariant->hasOptionValue($productOptionValue)) {
                $productVariant->addOptionValue($productOptionValue);
            }

            foreach ($this->getLocales() as $locale) {
                /** @var \Sylius\Component\Product\Model\ProductOptionValueTranslationInterface $productOptionValueTranslation */
                $productOptionValueTranslation = $this->productOptionValueTranslationRepository->findOneBy([
                    'translatable' => $productOptionValue,
                    'locale' => $locale,
                ]);

                if (!$productOptionValueTranslation instanceof ProductOptionValueTranslationInterface) {
                    continue;
                }

                $productVariantTranslation = $this->productVariantTranslationRepository->findOneBy([
                    'translatable' => $productVariant,
                    'locale' => $locale,
                ]);

                if (!$productVariantTranslation instanceof ProductVariantTranslationInterface) {
                    /** @var ProductVariantTranslationInterface $productVariantTranslation */
                    $productVariantTranslation = $this->productVariantTranslationFactory->createNew();
                    $this->entityManager->persist($productVariantTranslation);
                    $productVariantTranslation->setLocale($locale);
                    $productVariantTranslation->setTranslatable($productVariant);
                    $productVariant->addTranslation($productVariantTranslation);
                }

                $productVariantTranslation->setName($productOptionValueTranslation->getValue());
            }
        }
    }

    /**
     * @param array|string $data
     */
    private function getCode(ProductOptionInterface $productOption, $data): string
    {
        if (!\is_array($data)) {
            return ProductOptionManager::getOptionValueCodeFromProductOption(
                $productOption,
                CreateUpdateDeleteTask::AKENEO_PREFIX . $data
            );
        }

        return ProductOptionManager::getOptionValueCodeFromProductOption(
            $productOption,
            CreateUpdateDeleteTask::AKENEO_PREFIX . \implode('_', $data)
        );
    }

    /**
     * @param array|string $data
     */
    private function getValue($data): string
    {
        if (!\is_array($data)) {
            return $data;
        }

        return \implode(' ', $data);
    }

    private function updateImages(ProductPayload $payload, array $resource, ProductVariantInterface $productVariant): void
    {
        $productVariantMediaPayload = new ProductVariantMediaPayload($payload->getAkeneoPimClient());
        $productVariantMediaPayload
            ->setProductVariant($productVariant)
            ->setAttributes($resource)
        ;
        $imageTask = $this->taskProvider->get(InsertProductVariantImagesTask::class);
        $imageTask->__invoke($productVariantMediaPayload);
    }

    private function getOrCreateEntity(string $variantCode, ProductInterface $productModel): ProductVariantInterface
    {
        $productVariant = $this->productVariantRepository->findOneBy(['code' => $variantCode]);

        if (!$productVariant instanceof ProductVariantInterface) {
            /** @var ProductVariantInterface $productVariant */
            $productVariant = $this->productVariantFactory->createForProduct($productModel);
            $productVariant->setCode($variantCode);

            ++$this->createCount;
            $this->logger->info(Messages::hasBeenCreated($this->type, (string) $productVariant->getCode()));

            $this->entityManager->persist($productVariant);

            return $productVariant;
        }

        ++$this->updateCount;
        $this->logger->info(Messages::hasBeenUpdated($this->type, (string) $productVariant->getCode()));

        return $productVariant;
    }
}
