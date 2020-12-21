<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Manager;

use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeDataProvider;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;

final class ProductVariantTranslationModelAttributeManager extends AbstractTranslationModelAttributeManager implements ProductVariantTranslationModelAttributeManagerInterface
{
    private const NATIVE_PROPERTIES = [];

    /** @var string */
    private $productVariantClass;

    /** @var string */
    private $productVariantTranslationClass;

    public function __construct(
        CamelCaseToSnakeCaseNameConverter $camelCaseToSnakeCaseNameConverter,
        AkeneoAttributePropertiesProvider $akeneoAttributePropertyProvider,
        AkeneoAttributeDataProvider $akeneoAttributeDataProvider,
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        string $productVariantClass,
        string $productVariantTranslationClass
    ) {
        parent::__construct(
            $camelCaseToSnakeCaseNameConverter,
            $akeneoAttributePropertyProvider,
            $akeneoAttributeDataProvider,
            $syliusAkeneoLocaleCodeProvider
        );
        $this->productVariantClass = $productVariantClass;
        $this->productVariantTranslationClass = $productVariantTranslationClass;
    }

    public function hasRequiredMethodForAttribute(string $attributeCode): bool
    {
        return \method_exists(
            $this->getModelObjectFromAttribute($attributeCode),
            $this->getSetterMethodFromAttributeCode($attributeCode)
        );
    }

    public function setAkeneoAttributeToProductTranslationModel(
        ProductVariantInterface $product,
        string $attributeCode,
        array $translations,
        string $scope
    ): void {
        if (!$this->hasRequiredMethodForAttribute($attributeCode)) {
            throw new \Exception('Logic invalide'); //TODO: create real exception
        }

        foreach ($translations as $translation) {
            if ($translation['locale'] !== null
                && $this->syliusAkeneoLocaleCodeProvider->isActiveLocale($translation['locale']) === false) {
                continue;
            }

            if ($translation['locale'] === null) {
                foreach ($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms() as $locale) {
                    $this->setValueToMethod($product, $attributeCode, $translations, $locale, $scope);
                }

                continue;
            }

            $this->setValueToMethod($product, $attributeCode, $translations, $translation['locale'], $scope);
        }
    }

    private function getSetterMethodFromAttributeCode(string $attributeCode): string
    {
        if (\in_array($this->camelCaseToSnakeCaseNameConverter->normalize($attributeCode), self::NATIVE_PROPERTIES) ||
            in_array($this->camelCaseToSnakeCaseNameConverter->denormalize($attributeCode), self::NATIVE_PROPERTIES)
        ) {
            return $this->camelCaseToSnakeCaseNameConverter->denormalize(\sprintf(
                'set%s',
                \ucfirst($attributeCode)
            ));
        }

        return $this->camelCaseToSnakeCaseNameConverter->denormalize(\sprintf(
            'set%sAkeneoAttribute',
            \ucfirst($attributeCode)
        ));
    }

    private function setValueToMethod(
        ProductVariantInterface $product,
        string $attributeCode,
        array $translations,
        string $locale,
        string $scope
    ): void {
        $attributeValueValue = $this->akeneoAttributeDataProvider->getData(
            $attributeCode,
            $translations,
            $locale,
            $scope
        );

        if ($this->getModelObjectFromAttribute($attributeCode) !== $this->productVariantTranslationClass) {
            $reflectionMethod = new \ReflectionMethod(
                $product,
                $this->getSetterMethodFromAttributeCode($attributeCode)
            );
            $reflectionMethod->invoke($product, $attributeValueValue);

            return;
        }

        $translationModel = $product->getTranslation($locale);
        $reflectionMethod = new \ReflectionMethod(
            $translationModel,
            $this->getSetterMethodFromAttributeCode($attributeCode)
        );
        $reflectionMethod->invoke($translationModel, $attributeValueValue);
    }

    private function getModelObjectFromAttribute(string $attributeCode): string
    {
        if ($this->akeneoAttributePropertyProvider->isLocalizable($attributeCode)) {
            return $this->productVariantTranslationClass;
        }

        return $this->productVariantClass;
    }
}
