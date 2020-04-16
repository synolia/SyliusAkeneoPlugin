<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslation;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Product\Generator\SlugGeneratorInterface;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Synolia\SyliusAkeneoPlugin\Builder\ProductAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductResourcePayload;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

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

    public function __construct(
        RepositoryInterface $productAttributeValueRepository,
        RepositoryInterface $productAttributeRepository,
        RepositoryInterface $productTranslationRepository,
        FactoryInterface $productAttributeValueFactory,
        FactoryInterface $productTranslationFactory,
        SlugGeneratorInterface $productSlugGenerator,
        LocaleContextInterface $localeContext,
        ProductAttributeValueValueBuilder $attributeValueValueBuilder
    ) {
        $this->productAttributeValueRepository = $productAttributeValueRepository;
        $this->productTranslationRepository = $productTranslationRepository;
        $this->productAttributeValueFactory = $productAttributeValueFactory;
        $this->productTranslationFactory = $productTranslationFactory;
        $this->productSlugGenerator = $productSlugGenerator;
        $this->localeContext = $localeContext;
        $this->attributeValueValueBuilder = $attributeValueValueBuilder;
        $this->productAttributeRepository = $productAttributeRepository;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Product\ProductResourcePayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload instanceof ProductResourcePayload) {
            return $payload;
        }
        $this->productProperties = ['name', 'slug', 'description', 'metaDescription'];
        $this->caseConverter = new CamelCaseToSnakeCaseNameConverter();

        $productTranslationPropertyAttributesByLocale = $this->getProductTranslationPropertyByLocale($payload->getResource()['values']);

        $this->processProductTranslationAttributes(
            $payload->getProduct(),
            $productTranslationPropertyAttributesByLocale,
            $payload->getResource()['identifier'] ?? $payload->getResource()['code'],
            $payload->getResource()
        );

        foreach ($payload->getResource()['values'] as $attributeName => $translations) {
            if (\in_array($this->caseConverter->denormalize($attributeName), $this->productProperties, true)) {
                continue;
            }

            /** @var \Sylius\Component\Attribute\Model\AttributeInterface $attribute */
            $attribute = $this->productAttributeRepository->findOneBy(['code' => $attributeName]);
            if (!$attribute instanceof AttributeInterface || null === $attribute->getType()) {
                continue;
            }

            if (!$this->attributeValueValueBuilder->hasSupportedBuilder($attribute->getType())) {
                continue;
            }

            foreach ($translations as $translation) {
                $attributeValue = $this->productAttributeValueRepository->findOneBy([
                    'subject' => $payload->getProduct(),
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
                $payload->getProduct()->addAttribute($attributeValue);
            }
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
                    $resource['identifier'],
                ));
            }

            $productTranslation = $this->productTranslationRepository->findOneBy([
                'translatable' => $product,
                'locale' => $locale,
            ]);

            if (!$productTranslation instanceof ProductTranslationInterface) {
                $productTranslation = new ProductTranslation();
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
