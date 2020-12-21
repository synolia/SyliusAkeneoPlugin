<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Manager;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeDataProvider;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;

final class ProductTranslationModelAttributeManager extends AbstractTranslationModelAttributeManager implements ProductTranslationModelAttributeManagerInterface
{
    private const NATIVE_PROPERTIES = ['slug', 'description', 'short_description', 'meta_description', 'meta_keywords'];

    /** @var ProductTranslationInterface */
    private $productTranslationModel;

    /** @var string */
    private $productClass;

    /** @var string */
    private $productTranslationClass;

    public function __construct(
        FactoryInterface $productTranslationFactory,
        CamelCaseToSnakeCaseNameConverter $camelCaseToSnakeCaseNameConverter,
        AkeneoAttributePropertiesProvider $akeneoAttributePropertyProvider,
        AkeneoAttributeDataProvider $akeneoAttributeDataProvider,
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        string $productClass,
        string $productTranslationClass
    ) {
        $this->productTranslationModel = $productTranslationFactory->createNew();

        parent::__construct(
            $camelCaseToSnakeCaseNameConverter,
            $akeneoAttributePropertyProvider,
            $akeneoAttributeDataProvider,
            $syliusAkeneoLocaleCodeProvider
        );
        $this->productClass = $productClass;
        $this->productTranslationClass = $productTranslationClass;
    }

    public function hasRequiredMethodForAttribute(string $attributeCode): bool
    {
        return \method_exists(
            $this->getModelObjectFromAttribute($attributeCode),
            $this->getSetterMethodFromAttributeCode($attributeCode)
        );
    }

    public function setAkeneoAttributeToProductTranslationModel(
        ProductInterface $product,
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
        ProductInterface $product,
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

        if ($this->getModelObjectFromAttribute($attributeCode) !== $this->productTranslationClass) {
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
            return $this->productTranslationClass;
        }

        return $this->productClass;
    }
}
