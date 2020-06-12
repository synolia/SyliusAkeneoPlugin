<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Product\Generator\SlugGeneratorInterface;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Synolia\SyliusAkeneoPlugin\Builder\ProductAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoProductFiltersConfigurationException;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductResourcePayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeDataProvider;
use Synolia\SyliusAkeneoPlugin\Repository\ProductFiltersRulesRepository;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Transformer\AkeneoAttributeToSyliusAttributeTransformer;

final class AddAttributesToProductTask implements AkeneoTaskInterface
{
    /** @var string[] */
    private $productProperties;

    /** @var \Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter */
    private $caseConverter;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productAttributeValueRepository;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productAttributeValueFactory;

    /** @var \Sylius\Component\Product\Generator\SlugGeneratorInterface */
    private $productSlugGenerator;

    /** @var \Sylius\Component\Locale\Context\LocaleContextInterface */
    private $localeContext;

    /** @var \Synolia\SyliusAkeneoPlugin\Builder\ProductAttributeValueValueBuilder */
    private $attributeValueValueBuilder;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productAttributeRepository;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productTranslationRepository;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productTranslationFactory;

    /** @var EntityRepository */
    private $productConfigurationRepository;

    /** @var AkeneoAttributeToSyliusAttributeTransformer */
    private $akeneoAttributeToSyliusAttributeTransformer;

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeDataProvider */
    private $akeneoAttributeDataProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\Repository\ProductFiltersRulesRepository */
    private $productFiltersRulesRepository;

    /** @var SyliusAkeneoLocaleCodeProvider */
    private $syliusAkeneoLocaleCodeProvider;

    public function __construct(
        RepositoryInterface $productAttributeValueRepository,
        RepositoryInterface $productAttributeRepository,
        AkeneoAttributeToSyliusAttributeTransformer $akeneoAttributeToSyliusAttributeTransformer,
        RepositoryInterface $productTranslationRepository,
        FactoryInterface $productAttributeValueFactory,
        FactoryInterface $productTranslationFactory,
        SlugGeneratorInterface $productSlugGenerator,
        LocaleContextInterface $localeContext,
        ProductAttributeValueValueBuilder $attributeValueValueBuilder,
        EntityRepository $productConfigurationRepository,
        AkeneoAttributeDataProvider $akeneoAttributeDataProvider,
        ProductFiltersRulesRepository $productFiltersRulesRepository,
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider
    ) {
        $this->productAttributeValueRepository = $productAttributeValueRepository;
        $this->productTranslationRepository = $productTranslationRepository;
        $this->productAttributeValueFactory = $productAttributeValueFactory;
        $this->productTranslationFactory = $productTranslationFactory;
        $this->productSlugGenerator = $productSlugGenerator;
        $this->localeContext = $localeContext;
        $this->attributeValueValueBuilder = $attributeValueValueBuilder;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->productConfigurationRepository = $productConfigurationRepository;
        $this->akeneoAttributeToSyliusAttributeTransformer = $akeneoAttributeToSyliusAttributeTransformer;
        $this->akeneoAttributeDataProvider = $akeneoAttributeDataProvider;
        $this->productFiltersRulesRepository = $productFiltersRulesRepository;
        $this->syliusAkeneoLocaleCodeProvider = $syliusAkeneoLocaleCodeProvider;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Product\ProductResourcePayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload instanceof ProductResourcePayload) {
            return $payload;
        }

        /** @var \Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules $filters */
        $filters = $this->productFiltersRulesRepository->findOneBy([]);
        if (!$filters instanceof ProductFiltersRules) {
            throw new NoProductFiltersConfigurationException('Product filters must be configured before importing product attributes.');
        }
        $scope = $filters->getChannel();

        $this->productProperties = ['name', 'slug', 'description', 'metaDescription'];
        $this->caseConverter = new CamelCaseToSnakeCaseNameConverter();

        $productTranslationPropertyAttributesByLocale = $this->getProductTranslationPropertyByLocale($payload->getResource()['values']);

        $this->processProductTranslationAttributes(
            $payload->getProduct(),
            $productTranslationPropertyAttributesByLocale,
            $payload->getResource()['identifier'] ?? $payload->getResource()['code'],
            $payload->getResource()
        );

        foreach ($payload->getResource()['values'] as $attributeCode => $translations) {
            $transformedAttributeCode = $this->akeneoAttributeToSyliusAttributeTransformer->transform($attributeCode);
            if (\in_array($this->caseConverter->denormalize($transformedAttributeCode), $this->productProperties, true)) {
                continue;
            }
            /** @var \Sylius\Component\Attribute\Model\AttributeInterface $attribute */
            $attribute = $this->productAttributeRepository->findOneBy(['code' => $transformedAttributeCode]);
            if (!$attribute instanceof AttributeInterface || null === $attribute->getType()) {
                continue;
            }

            if (!$this->attributeValueValueBuilder->hasSupportedBuilder($attributeCode)) {
                continue;
            }

            foreach ($translations as $translation) {
                $this->setAttributeTranslations($payload, $attribute, $translations, $translation, $attributeCode, $scope);
            }
        }

        return $payload;
    }

    private function setAttributeTranslations(
        ProductResourcePayload $payload,
        AttributeInterface $attribute,
        array $translations,
        array $translation,
        string $attributeCode,
        string $scope
    ): void {
        if ($translation['locale'] !== null && $this->isActiveLocale($translation['locale']) === false) {
            return;
        }

        if ($translation['locale'] === null) {
            foreach ($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms() as $locale) {
                $this->setAttributeTranslation($payload, $attribute, $translations, $locale, $attributeCode, $scope);
            }

            return;
        }

        $this->setAttributeTranslation($payload, $attribute, $translations, $translation['locale'], $attributeCode, $scope);
    }

    private function setAttributeTranslation(
        ProductResourcePayload $payload,
        AttributeInterface $attribute,
        array $translations,
        string $locale,
        string $attributeCode,
        string $scope
    ): void {
        $attributeValue = $this->productAttributeValueRepository->findOneBy([
            'subject' => $payload->getProduct(),
            'attribute' => $attribute,
            'localeCode' => $locale,
        ]);

        if (!$attributeValue instanceof ProductAttributeValueInterface) {
            /** @var \Sylius\Component\Product\Model\ProductAttributeValueInterface $attributeValue */
            $attributeValue = $this->productAttributeValueFactory->createNew();
        }

        $localeValues = $this->getLocaleTranslations($attribute, $translations, $locale);
        if ($localeValues === null) {
            return;
        }

        $attributeValue->setLocaleCode($locale);
        $attributeValue->setAttribute($attribute);
        $attributeValueValue = $this->akeneoAttributeDataProvider->getData($attributeCode, $localeValues, $locale, $scope);
        $attributeValue->setValue($attributeValueValue);
        $payload->getProduct()->addAttribute($attributeValue);
    }

    private function getLocaleTranslations(AttributeInterface $attribute, array $translations, string $locale): ?array
    {
        $localeTranslations = [];
        if (count($translations) > 1) {
            foreach ($translations as $translation) {
                if ($this->isLocaleDataTranslation($attribute, $translation, $locale) === false) {
                    continue;
                }
                $localeTranslations[] = $translation;
            }

            return $localeTranslations;
        }

        if ($attribute->getConfiguration() === []) {
            return $translations;
        }

        if (!is_array($translations[0]['data'])) {
            $isLocaleDataValues = $this->isLocaleDataTranslation($attribute, $translations[0]['data'], $locale);
            if ($isLocaleDataValues === false) {
                return null;
            }

            return $translations;
        }

        $datas = [];
        foreach ($translations[0]['data'] as $data) {
            if ($this->isLocaleDataTranslation($attribute, $data, $locale) === false) {
                continue;
            }
            $datas[] = $data;
        }

        $translations[0]['data'] = $datas;

        return $translations;
    }

    /**
     * @param array|string $data
     */
    private function isLocaleDataTranslation(AttributeInterface $attribute, $data, string $locale): bool
    {
        if (isset($attribute->getConfiguration()['choices'][$data]) && array_key_exists($locale, $attribute->getConfiguration()['choices'][$data])) {
            return true;
        }

        if (is_array($data) && $data['locale'] === $locale) {
            return true;
        }

        return false;
    }

    private function isActiveLocale(string $locale): bool
    {
        $locales = $this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms();

        return in_array($locale, $locales) ? true : false;
    }

    private function getProductTranslationPropertyByLocale(array $attributes): array
    {
        $productTranslationPropertyAttributesByLocale = [];
        foreach ($attributes as $attributeName => $translations) {
            $denormalizedPropertyName = $this->caseConverter->denormalize($attributeName);
            if (\in_array($denormalizedPropertyName, $this->productProperties, true)) {
                foreach ($translations as $translation) {
                    $productTranslationPropertyAttributesByLocale[$translation['locale'] ?? $this->localeContext->getLocaleCode()][$attributeName] = $translation['data'];
                }

                continue;
            }
        }

        return $productTranslationPropertyAttributesByLocale ?? [];
    }

    private function processProductTranslationAttributes(
        ProductInterface $product,
        array $translations,
        string $identifier,
        array $resource
    ): void {
        foreach ($translations as $locale => $translation) {
            $productName = $this->findAttributeValueForLocale($resource, 'name', $locale);

            if (null === $productName) {
                throw new \LogicException(\sprintf(
                    'Could not find required attribute "%s" for product "%s".',
                    'name',
                    $resource['identifier'],
                ));
            }

            $productTranslation = $this->productTranslationRepository->findOneBy([
                'translatable' => $product,
                'locale' => $locale,
            ]);

            if (!$productTranslation instanceof ProductTranslationInterface) {
                /** @var ProductTranslationInterface $productTranslation */
                $productTranslation = $this->productTranslationFactory->createNew();
                $productTranslation->setLocale($locale);
                $product->addTranslation($productTranslation);
            }

            $productTranslation->setName($productName);

            if (isset($translation['description'])) {
                $productTranslation->setDescription($this->findAttributeValueForLocale($resource, 'description', $locale));
            }

            if (isset($translation['meta_keywords'])) {
                $productTranslation->setMetaKeywords($this->findAttributeValueForLocale($resource, 'meta_keywords', $locale));
            }

            if (isset($translation['meta_description'])) {
                $productTranslation->setMetaDescription($this->findAttributeValueForLocale($resource, 'meta_description', $locale));
            }

            /** @var ProductConfiguration $configuration */
            $configuration = $this->productConfigurationRepository->findOneBy([]);
            if ($product->getId() !== null && $configuration !== null && $configuration->getRegenerateUrlRewrites() === false) {
                // no regenerate slug if config disable it

                continue;
            }

            //Multiple product has the same name
            $productTranslation->setSlug($identifier . '-' . $this->productSlugGenerator->generate($productName));
        }
    }

    private function findAttributeValueForLocale(array $resource, string $attributeCode, string $locale): ?string
    {
        if (!isset($resource['values'][$attributeCode])) {
            return null;
        }

        foreach ($resource['values'][$attributeCode] as $translation) {
            if (null === $translation['locale']) {
                return $translation['data'];
            }

            if ($locale !== $translation['locale']) {
                continue;
            }

            return $translation['data'];
        }

        return null;
    }
}
