<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute;

use Psr\Log\LoggerInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Builder\Attribute\ProductAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeDataProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Transformer\AkeneoAttributeToSyliusAttributeTransformerInterface;

final class ProductAttributeAkeneoAttributeProcessor implements AkeneoAttributeProcessorInterface
{
    private AkeneoAttributeDataProviderInterface $akeneoAttributeDataProvider;

    private SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider;

    private AkeneoAttributeToSyliusAttributeTransformerInterface $akeneoAttributeToSyliusAttributeTransformer;

    private RepositoryInterface $productAttributeRepository;

    private RepositoryInterface $productAttributeValueRepository;

    private ProductAttributeValueValueBuilder $attributeValueValueBuilder;

    private FactoryInterface $productAttributeValueFactory;

    private LoggerInterface $logger;

    public function __construct(
        AkeneoAttributeDataProviderInterface $akeneoAttributeDataProvider,
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        AkeneoAttributeToSyliusAttributeTransformerInterface $akeneoAttributeToSyliusAttributeTransformer,
        RepositoryInterface $productAttributeRepository,
        RepositoryInterface $productAttributeValueRepository,
        ProductAttributeValueValueBuilder $attributeValueValueBuilder,
        FactoryInterface $productAttributeValueFactory,
        LoggerInterface $akeneoLogger
    ) {
        $this->akeneoAttributeDataProvider = $akeneoAttributeDataProvider;
        $this->syliusAkeneoLocaleCodeProvider = $syliusAkeneoLocaleCodeProvider;
        $this->akeneoAttributeToSyliusAttributeTransformer = $akeneoAttributeToSyliusAttributeTransformer;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->productAttributeValueRepository = $productAttributeValueRepository;
        $this->attributeValueValueBuilder = $attributeValueValueBuilder;
        $this->productAttributeValueFactory = $productAttributeValueFactory;
        $this->logger = $akeneoLogger;
    }

    public static function getDefaultPriority(): int
    {
        return -100;
    }

    public function support(string $attributeCode, array $context = []): bool
    {
        $transformedAttributeCode = $this->akeneoAttributeToSyliusAttributeTransformer->transform($attributeCode);

        /** @var AttributeInterface $attribute */
        $attribute = $this->productAttributeRepository->findOneBy(['code' => $transformedAttributeCode]);

        if (!$attribute instanceof AttributeInterface || null === $attribute->getType()) {
            return false;
        }

        if (!$this->attributeValueValueBuilder->hasSupportedBuilder($attributeCode)) {
            return false;
        }

        return true;
    }

    public function process(string $attributeCode, array $context = []): void
    {
        $this->logger->debug(sprintf(
            'Attribute "%s" is beeing processed by "%s"',
            $attributeCode,
            static::class
        ));

        if (!$context['model'] instanceof ProductInterface) {
            return;
        }

        $transformedAttributeCode = $this->akeneoAttributeToSyliusAttributeTransformer->transform($attributeCode);

        /** @var AttributeInterface $attribute */
        $attribute = $this->productAttributeRepository->findOneBy(['code' => $transformedAttributeCode]);

        foreach ($context['data'] as $translation) {
            if (null !== $translation['locale'] && false === $this->syliusAkeneoLocaleCodeProvider->isActiveLocale($translation['locale'])) {
                continue;
            }

            if (null === $translation['locale']) {
                foreach ($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms() as $locale) {
                    $this->setAttributeTranslation(
                        $context['model'],
                        $attribute,
                        $context['data'],
                        $locale,
                        $attributeCode,
                        $context['scope']
                    );
                }

                continue;
            }

            $this->setAttributeTranslation(
                $context['model'],
                $attribute,
                $context['data'],
                $translation['locale'],
                $attributeCode,
                $context['scope']
            );
        }
    }

    private function setAttributeTranslation(
        ProductInterface $product,
        AttributeInterface $attribute,
        array $translations,
        string $locale,
        string $attributeCode,
        string $scope
    ): void {
        $attributeValue = $this->productAttributeValueRepository->findOneBy([
            'subject' => $product,
            'attribute' => $attribute,
            'localeCode' => $locale,
        ]);

        if (!$attributeValue instanceof ProductAttributeValueInterface) {
            /** @var \Sylius\Component\Product\Model\ProductAttributeValueInterface $attributeValue */
            $attributeValue = $this->productAttributeValueFactory->createNew();
        }

        $attributeValue->setLocaleCode($locale);
        $attributeValue->setAttribute($attribute);
        $attributeValueValue = $this->akeneoAttributeDataProvider->getData($attributeCode, $translations, $locale, $scope);
        $attributeValue->setValue($attributeValueValue);
        $product->addAttribute($attributeValue);
    }
}
