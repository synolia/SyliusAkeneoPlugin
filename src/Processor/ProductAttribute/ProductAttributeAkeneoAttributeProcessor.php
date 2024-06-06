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
use Synolia\SyliusAkeneoPlugin\Component\Attribute\AttributeType\AssetAttributeType;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationOrScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\TranslationNotFoundException;
use Synolia\SyliusAkeneoPlugin\Provider\Data\AkeneoAttributeDataProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Transformer\AkeneoAttributeToSyliusAttributeTransformerInterface;

final class ProductAttributeAkeneoAttributeProcessor implements AkeneoAttributeProcessorInterface
{
    public function __construct(
        private AkeneoAttributeDataProviderInterface $akeneoAttributeDataProvider,
        private SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        private AkeneoAttributeToSyliusAttributeTransformerInterface $akeneoAttributeToSyliusAttributeTransformer,
        private RepositoryInterface $productAttributeRepository,
        private RepositoryInterface $productAttributeValueRepository,
        private ProductAttributeValueValueBuilder $attributeValueValueBuilder,
        private FactoryInterface $productAttributeValueFactory,
        private LoggerInterface $logger,
    ) {
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

        if ($attribute->getType() === AssetAttributeType::TYPE) {
            return false;
        }

        return true;
    }

    public function process(string $attributeCode, array $context = []): void
    {
        $this->logger->debug(sprintf(
            'Attribute "%s" is beeing processed by "%s"',
            $attributeCode,
            static::class,
        ));

        if (!$context['model'] instanceof ProductInterface) {
            return;
        }

        $transformedAttributeCode = $this->akeneoAttributeToSyliusAttributeTransformer->transform($attributeCode);

        /** @var AttributeInterface $attribute */
        $attribute = $this->productAttributeRepository->findOneBy(['code' => $transformedAttributeCode]);

        foreach ($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms() as $syliusLocale) {
            try {
                $this->setAttributeTranslation(
                    $context['model'],
                    $attribute,
                    $context['data'],
                    $syliusLocale,
                    $attributeCode,
                    $context['scope'],
                );
            } catch (MissingLocaleTranslationException | MissingLocaleTranslationOrScopeException|MissingScopeException|TranslationNotFoundException $error) {
                $this->logger->debug('Attribute translation error', [
                    'attribute_code' => $attributeCode,
                    'sylius_locale' => $syliusLocale,
                    'context' => $context,
                    'error' => $error->getMessage(),
                    'trace' => $error->getTraceAsString(),
                ]);
            }
        }
    }

    /**
     * @throws MissingLocaleTranslationOrScopeException
     * @throws MissingLocaleTranslationException
     * @throws MissingScopeException
     * @throws TranslationNotFoundException
     */
    private function setAttributeTranslation(
        ProductInterface $product,
        AttributeInterface $attribute,
        array $translations,
        string $syliusLocale,
        string $attributeCode,
        string $scope,
    ): void {
        $akeneoLocale = $this->syliusAkeneoLocaleCodeProvider->getAkeneoLocale($syliusLocale);

        $attributeValue = $this->productAttributeValueRepository->findOneBy([
            'subject' => $product,
            'attribute' => $attribute,
            'localeCode' => $syliusLocale,
        ]);

        if (!$attributeValue instanceof ProductAttributeValueInterface) {
            /** @var \Sylius\Component\Product\Model\ProductAttributeValueInterface $attributeValue */
            $attributeValue = $this->productAttributeValueFactory->createNew();
        }

        $attributeValue->setLocaleCode($syliusLocale);
        $attributeValue->setAttribute($attribute);
        $attributeValueValue = $this->akeneoAttributeDataProvider->getData($attributeCode, $translations, $akeneoLocale, $scope);
        $attributeValue->setValue($attributeValueValue);
        $product->addAttribute($attributeValue);
    }
}
