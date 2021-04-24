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
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Transformer\AkeneoAttributeToSyliusAttributeTransformer;

class ProductAttributeAkeneoAttributeProcessor implements AkeneoAttributeProcessorInterface
{
    /** @var AkeneoAttributeDataProviderInterface */
    private $akeneoAttributeDataProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider */
    private $syliusAkeneoLocaleCodeProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\Transformer\AkeneoAttributeToSyliusAttributeTransformer */
    private $akeneoAttributeToSyliusAttributeTransformer;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productAttributeRepository;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productAttributeValueRepository;

    /** @var \Synolia\SyliusAkeneoPlugin\Builder\Attribute\ProductAttributeValueValueBuilder */
    private $attributeValueValueBuilder;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productAttributeValueFactory;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    public function __construct(
        AkeneoAttributeDataProviderInterface $akeneoAttributeDataProvider,
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        AkeneoAttributeToSyliusAttributeTransformer $akeneoAttributeToSyliusAttributeTransformer,
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
        $transformedAttributeCode = $this->akeneoAttributeToSyliusAttributeTransformer->transform((string) $attributeCode);

        /** @var AttributeInterface $attribute */
        $attribute = $this->productAttributeRepository->findOneBy(['code' => $transformedAttributeCode]);

        if (!$attribute instanceof AttributeInterface || null === $attribute->getType()) {
            return false;
        }

        if (!$this->attributeValueValueBuilder->hasSupportedBuilder((string) $attributeCode)) {
            return false;
        }

        return true;
    }

    public function process(string $attributeCode, array $context = []): void
    {
        $this->logger->debug(\sprintf(
            'Attribute "%s" is beeing processed by "%s"',
            $attributeCode,
            \get_class($this)
        ));

        if (!$context['model'] instanceof ProductInterface) {
            return;
        }

        $transformedAttributeCode = $this->akeneoAttributeToSyliusAttributeTransformer->transform((string) $attributeCode);

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
