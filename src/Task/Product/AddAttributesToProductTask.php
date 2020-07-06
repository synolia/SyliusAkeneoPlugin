<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Psr\Log\LoggerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Sylius\Component\Product\Generator\SlugGeneratorInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Synolia\SyliusAkeneoPlugin\Builder\ProductAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoProductFiltersConfigurationException;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\LocaleAttributeTranslationPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductResourcePayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Repository\ProductFiltersRulesRepository;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\LocaleAttributeTranslationTask;
use Synolia\SyliusAkeneoPlugin\Transformer\AkeneoAttributeToSyliusAttributeTransformer;

final class AddAttributesToProductTask implements AkeneoTaskInterface
{
    /** @var string[] */
    private $productProperties;

    /** @var \Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter */
    private $caseConverter;

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

    /** @var \Synolia\SyliusAkeneoPlugin\Repository\ProductFiltersRulesRepository */
    private $productFiltersRulesRepository;

    /** @var LoggerInterface */
    private $akeneoLogger;

    /** @var ProductFactoryInterface */
    private $productFactory;

    /** @var AkeneoTaskProvider */
    private $taskProvider;

    public function __construct(
        LoggerInterface $akeneoLogger,
        RepositoryInterface $productAttributeRepository,
        AkeneoAttributeToSyliusAttributeTransformer $akeneoAttributeToSyliusAttributeTransformer,
        RepositoryInterface $productTranslationRepository,
        FactoryInterface $productTranslationFactory,
        SlugGeneratorInterface $productSlugGenerator,
        LocaleContextInterface $localeContext,
        ProductAttributeValueValueBuilder $attributeValueValueBuilder,
        EntityRepository $productConfigurationRepository,
        ProductFiltersRulesRepository $productFiltersRulesRepository,
        ProductFactoryInterface $productFactory,
        AkeneoTaskProvider $taskProvider
    ) {
        $this->productTranslationRepository = $productTranslationRepository;
        $this->productTranslationFactory = $productTranslationFactory;
        $this->productSlugGenerator = $productSlugGenerator;
        $this->localeContext = $localeContext;
        $this->attributeValueValueBuilder = $attributeValueValueBuilder;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->productConfigurationRepository = $productConfigurationRepository;
        $this->akeneoAttributeToSyliusAttributeTransformer = $akeneoAttributeToSyliusAttributeTransformer;
        $this->productFiltersRulesRepository = $productFiltersRulesRepository;
        $this->akeneoLogger = $akeneoLogger;
        $this->productFactory = $productFactory;
        $this->taskProvider = $taskProvider;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Product\ProductResourcePayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload instanceof ProductResourcePayload || $payload->getProduct() === null) {
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

        try {
            $this->processProductTranslationAttributes(
                $payload->getProduct(),
                $productTranslationPropertyAttributesByLocale,
                $payload->getResource()['identifier'] ?? $payload->getResource()['code'],
                $payload->getResource()
            );
        } catch (\LogicException $e) {
            $this->akeneoLogger->alert($e->getMessage());

            $payload->setProduct(null);

            return $payload;
        }

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

            $this->setAttributeTranslations($payload, $translations, $attribute, $attributeCode, $scope);
        }

        return $payload;
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
                    $resource['identifier'] ?? $resource['code'],
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

    private function setAttributeTranslations(
        ProductResourcePayload $payload,
        array $translations,
        AttributeInterface $attribute,
        string $attributeCode,
        string $scope
    ): void {
        foreach ($translations as $translation) {
            if (!$payload->getProduct() instanceof ProductInterface) {
                return;
            }

            $localeAttributeTranslationPayload = new LocaleAttributeTranslationPayload($payload->getAkeneoPimClient());
            $localeAttributeTranslationPayload
                ->setProduct($payload->getProduct())
                ->setAttribute($attribute)
                ->setTranslations($translations)
                ->setTranslation($translation)
                ->setAttributeCode($attributeCode)
                ->setScope($scope);
            $localeAttributeTranslationTask = $this->taskProvider->get(LocaleAttributeTranslationTask::class);
            $localeAttributeTranslationTask->__invoke($localeAttributeTranslationPayload);
        }
    }
}
