<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Akeneo\Pim\ApiClient\Pagination\Page;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTaxonInterface;
use Sylius\Component\Core\Model\ProductTranslation;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Product\Factory\ProductFactory;
use Sylius\Component\Product\Generator\SlugGeneratorInterface;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslationInterface;
use Sylius\Component\Product\Model\ProductVariantTranslation;
use Sylius\Component\Product\Model\ProductVariantTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Synolia\SyliusAkeneoPlugin\Builder\ProductAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Helper\ClassHelper;
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class RetrieveProductsTask implements AkeneoTaskInterface
{
    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productRepository;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productFactory;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $taxonRepository;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productTaxonRepository;

    /** @var \Sylius\Component\Product\Generator\SlugGeneratorInterface */
    private $productSlugGenerator;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $attributeRepository;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productTaxonFactory;

    /** @var string[]|null */
    private $productProperties;

    /** @var \Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter */
    private $caseConverter;

    /** @var \Synolia\SyliusAkeneoPlugin\Helper\ClassHelper */
    private $classHelper;

    /** @var \Sylius\Component\Locale\Context\LocaleContextInterface */
    private $localeContext;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productAttributeValueFactory;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productAttributeValueRepository;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $channelRepository;

    /** @var \Synolia\SyliusAkeneoPlugin\Builder\ProductAttributeValueValueBuilder */
    private $attributeValueValueBuilder;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productVariantFactory;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productVariantRepository;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $channelPricingFactory;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $channelPricingRepository;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productOptionRepository;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productOptionFactory;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productOptionValueRepository;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productOptionValueFactory;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $localeRepository;

    public function __construct(
        RepositoryInterface $productRepository,
        RepositoryInterface $taxonRepository,
        RepositoryInterface $productTaxonRepository,
        RepositoryInterface $productAttributeRepository,
        RepositoryInterface $productAttributeValueRepository,
        RepositoryInterface $channelRepository,
        RepositoryInterface $productVariantRepository,
        RepositoryInterface $channelPricingRepository,
        RepositoryInterface $productOptionRepository,
        RepositoryInterface $productOptionValueRepository,
        RepositoryInterface $localeRepository,
        FactoryInterface $productFactory,
        FactoryInterface $productTaxonFactory,
        FactoryInterface $productAttributeValueFactory,
        FactoryInterface $productVariantFactory,
        FactoryInterface $channelPricingFactory,
        FactoryInterface $productOptionFactory,
        FactoryInterface $productOptionValueFactory,
        EntityManagerInterface $entityManager,
        SlugGeneratorInterface $productSlugGenerator,
        LocaleContextInterface $localeContext,
        ProductAttributeValueValueBuilder $attributeValueValueBuilder
    ) {
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->entityManager = $entityManager;
        $this->taxonRepository = $taxonRepository;
        $this->productTaxonRepository = $productTaxonRepository;
        $this->productTaxonFactory = $productTaxonFactory;
        $this->productAttributeValueFactory = $productAttributeValueFactory;
        $this->productAttributeValueRepository = $productAttributeValueRepository;
        $this->productSlugGenerator = $productSlugGenerator;
        $this->attributeRepository = $productAttributeRepository;
        $this->channelPricingRepository = $channelPricingRepository;
        $this->productOptionRepository = $productOptionRepository;
        $this->localeContext = $localeContext;
        $this->channelRepository = $channelRepository;
        $this->attributeValueValueBuilder = $attributeValueValueBuilder;
        $this->productVariantFactory = $productVariantFactory;
        $this->productVariantRepository = $productVariantRepository;
        $this->channelPricingFactory = $channelPricingFactory;
        $this->productOptionFactory = $productOptionFactory;
        $this->productOptionValueRepository = $productOptionValueRepository;
        $this->productOptionValueFactory = $productOptionValueFactory;
        $this->localeRepository = $localeRepository;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        // $this->classHelper = new ClassHelper();
        // $this->productProperties = $this->classHelper->getPropertyInfoExtractor()->getProperties(ProductInterface::class);
        $this->productProperties = ['name', 'slug', 'description', 'metaDescription'];
        $this->caseConverter = new CamelCaseToSnakeCaseNameConverter();

        $payload->setResources($payload->getAkeneoPimClient()->getProductApi()->listPerPage(100));

        if (!$payload->getResources() instanceof Page) {
            return $payload;
        }

        $this->entityManager->beginTransaction();

        while ($payload->getResources()->hasNextPage()) {
            foreach ($payload->getResources()->getItems() as $item) {
                $this->handleSimpleProduct($item);
                $this->handleConfigurableProduct($item);
            }
            $payload->setResources($payload->getResources()->getNextPage());
        }
        $this->entityManager->flush();
        $this->entityManager->commit();

        return $payload;
    }

    private function getOrCreateEntity(array $resource): ProductInterface
    {
        /** @var \Sylius\Component\Core\Model\ProductInterface $product */
        $product = $this->productRepository->findOneBy(['code' => $resource['identifier']]);

        if (!$product instanceof ProductInterface) {
            if (!$this->productFactory instanceof ProductFactory) {
                throw new \LogicException('Wrong Factory');
            }

            if (null === $resource['parent']) {
                $product = $this->productFactory->createNew();
            }

            $product->setCode($resource['identifier']);
            $this->entityManager->persist($product);
        }

        return $product;
    }

    private function linkCategoriesToProduct(ProductInterface $product, array $categories): void
    {
        foreach ($categories as $category) {
            $taxon = $this->taxonRepository->findOneBy(['code' => $category]);
            if (!$taxon instanceof TaxonInterface) {
                continue;
            }
            /** @var ProductTaxonInterface $productTaxon */
            $productTaxon = $this->productTaxonRepository->findOneBy(['product' => $product, 'taxon' => $taxon]);

            if (!$productTaxon instanceof ProductTaxonInterface) {
                /** @var ProductTaxonInterface $productTaxon */
                $productTaxon = $this->productTaxonFactory->createNew();
                $productTaxon->setProduct($product);
                $productTaxon->setTaxon($taxon);
                $this->entityManager->persist($productTaxon);
            }

            $product->addProductTaxon($productTaxon);
        }
    }

    private function insertAttributesToProduct(ProductInterface $product, $item): void
    {
        $productTranslationPropertyAttributesByLocale = [];
        foreach ($item['values'] as $attributeName => $translations) {
            $denormalizedPropertyName = $this->caseConverter->denormalize($attributeName);
            if (\in_array($denormalizedPropertyName, $this->productProperties, true)) {
                foreach ($translations as $translation) {
                    $productTranslationPropertyAttributesByLocale[$translation['locale'] ?? $this->localeContext->getLocaleCode()][$attributeName] = $translation['data'];
                }

                continue;
            }
        }

        $this->processProductTranslationAttributes(
            $product,
            $productTranslationPropertyAttributesByLocale,
            $item['identifier']
        );

        foreach ($item['values'] as $attributeName => $translations) {
            if (\in_array($this->caseConverter->denormalize($attributeName), $this->productProperties, true)) {
                continue;
            }

            /** @var \Sylius\Component\Attribute\Model\AttributeInterface $attribute */
            $attribute = $this->attributeRepository->findOneBy(['code' => $attributeName]);
            if (!$attribute instanceof AttributeInterface) {
                continue;
            }

            if (!$this->attributeValueValueBuilder->hasSupportedBuilder($attribute->getType())) {
                continue;
            }

            foreach ($translations as $translation) {
                $attributeValue = $this->productAttributeValueRepository->findOneBy([
                    'subject' => $product,
                    'attribute' => $attribute,
                    'localeCode' => $translation['locale'] ?? $this->localeContext->getLocaleCode(),
                ]);

                if (!$attributeValue instanceof ProductAttributeValueInterface) {
                    /** @var \Sylius\Component\Product\Model\ProductAttributeValueInterface $attributeValue */
                    $attributeValue = $this->productAttributeValueFactory->createNew();
                }

                $attributeValue->setLocaleCode($translation['locale'] ?? $this->localeContext->getLocaleCode());
                $attributeValue->setAttribute($attribute);

                $attributeValueValue = $this->attributeValueValueBuilder->build(
                    $attribute->getType(),
                    $translation['data']
                );
                $attributeValue->setValue($attributeValueValue);
                $product->addAttribute($attributeValue);
            }
        }
    }

    private function processProductTranslationAttributes(
        ProductInterface $product,
        array $translations,
        string $identifier
    ): void {
        foreach ($translations as $locale => $translation) {
            //Skip uncomplete translation
            if (!isset($translation['name'])) {
                continue;
            }

            $productTranslation = $product->getTranslation($locale);

            if (!$productTranslation instanceof ProductTranslationInterface) {
                $productTranslation = new ProductTranslation();
                $productTranslation->setLocale($locale);
                $product->addTranslation($productTranslation);
            }

            $productTranslation->setName($translation['name']);
            //Multiple product has the same name
            $productTranslation->setSlug($identifier . '-' . $this->productSlugGenerator->generate($translation['name']));
        }
    }

    private function getOrCreateSimpleVariant(ProductInterface $product): ProductVariantInterface
    {
        /** @var ProductVariantInterface $productVariant */
        $productVariant = $this->productVariantRepository->findOneBy(['code' => $product->getCode()]);

        if (!$productVariant instanceof ProductVariantInterface) {
            $productVariant = $this->productVariantFactory->createForProduct($product);
            $productVariant->setCode($product->getCode());

            $this->entityManager->persist($productVariant);
        }

        return $productVariant;
    }

    private function handleSimpleProduct($item): void
    {
        if ($item['parent'] !== null) {
            return;
        }

        $product = $this->getOrCreateEntity($item);
        $productVariant = $this->getOrCreateSimpleVariant($product);
        $this->linkCategoriesToProduct($product, $item['categories']);
        $this->insertAttributesToProduct($product, $item);

        //TODO: for each channel, create or update channel pricing
        /** @var \Sylius\Component\Core\Model\ChannelInterface $channel */
        foreach ($this->channelRepository->findAll() as $channel) {
            /** @var \Sylius\Component\Core\Model\ChannelPricingInterface $channelPricing */
            $channelPricing = $this->channelPricingRepository->findOneBy([
                'channelCode' => $channel->getCode(),
                'productVariant' => $productVariant,
            ]);

            if (!$channelPricing instanceof ChannelPricingInterface) {
                /** @var \Sylius\Component\Core\Model\ChannelPricingInterface $channelPricing */
                $channelPricing = $this->channelPricingFactory->createNew();
            }

            $channelPricing->setOriginalPrice(0);
            $channelPricing->setPrice(0);
            $channelPricing->setProductVariant($productVariant);
            $channelPricing->setChannelCode($channel->getCode());

            $productVariant->addChannelPricing($channelPricing);
        }

        //Temporary enabling product to all channels
        foreach ($this->channelRepository->findAll() as $channel) {
            $product->addChannel($channel);
        }
    }

    private function handleConfigurableProduct($item): void
    {
        if ($item['parent'] === null) {
            return;
        }

        /** @var ProductInterface $productModel */
        $productModel = $this->productRepository->findOneBy(['code' => $item['parent']]);

        //Skip product variant import if it does not have a parent model on Sylius
        if (!$productModel instanceof ProductInterface) {
            return;
        }

        //Use fake variation axe "size" for testing purpose
        $variationAxes = ['size'];

        foreach ($item['values'] as $attributeCode => $values) {
            /*
             * Skip attributes that aren't variation axes.
             * Variation axes value will be created as option for the product
             */
            if (!in_array($attributeCode, $variationAxes, true)) {
                continue;
            }

            /** @var ProductOptionInterface $productOption */
            $productOption = $this->productOptionRepository->findOneBy(['code' => $attributeCode]);

            //We cannot create the variant if the option does not exists
            if (!$productOption instanceof ProductOptionInterface) {
                continue;
            }

            if ($productModel->hasOption($productOption)) {
                $productModel->addOption($productOption);
            }

            $productVariant = $this->productVariantRepository->findOneBy(['code' => $item['identifier']]);

            if (!$productVariant instanceof ProductVariantInterface) {
                /** @var ProductVariantInterface $productVariant */
                $productVariant = $this->productVariantFactory->createForProduct($productModel);
                $productVariant->setCode($item['identifier']);

                $this->entityManager->persist($productVariant);
            }

            foreach ($values as $optionValue) {
                $productOptionValue = $this->productOptionValueRepository->findOneBy([
                    'option' => $productOption,
                    'code' => $optionValue,
                ]);

                if (!$productOptionValue instanceof ProductOptionValueInterface) {
                    continue;
                }

                //Product variant already have this value
                if (!$productVariant->hasOptionValue($productOptionValue)) {
                    $productVariant->addOptionValue($productOptionValue);
                }

                foreach ($this->getLocales() as $locale) {
                    /** @var \Sylius\Component\Product\Model\ProductOptionValueTranslationInterface $productOptionValueTranslation */
                    $productOptionValueTranslation = $productOptionValue->getTranslation($locale);

                    if (!$productOptionValueTranslation instanceof ProductOptionValueTranslationInterface) {
                        continue;
                    }

                    $productVariantTranslation = $productVariant->getTranslation($locale);

                    if (!$productVariantTranslation instanceof ProductVariantTranslationInterface) {
                        $productVariantTranslation = new ProductVariantTranslation();
                        $productVariantTranslation->setLocale($locale);
                        $productVariant->addTranslation($productVariantTranslation);
                    }

                    $productVariantTranslation->setName($productOptionValueTranslation->getValue());
                }
            }

            //TODO: for each channel, create or update channel pricing
            /** @var \Sylius\Component\Core\Model\ChannelInterface $channel */
            foreach ($this->channelRepository->findAll() as $channel) {
                /** @var \Sylius\Component\Core\Model\ChannelPricingInterface $channelPricing */
                $channelPricing = $this->channelPricingRepository->findOneBy([
                    'channelCode' => $channel->getCode(),
                    'productVariant' => $productVariant,
                ]);

                if (!$channelPricing instanceof ChannelPricingInterface) {
                    /** @var \Sylius\Component\Core\Model\ChannelPricingInterface $channelPricing */
                    $channelPricing = $this->channelPricingFactory->createNew();
                }

                $channelPricing->setOriginalPrice(0);
                $channelPricing->setPrice(0);
                $channelPricing->setProductVariant($productVariant);
                $channelPricing->setChannelCode($channel->getCode());

                $productVariant->addChannelPricing($channelPricing);
            }
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
}
