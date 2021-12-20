<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute;

use ReflectionMethod;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

final class ProductVariantTranslationModelAkeneoAttributeProcessor extends AbstractModelAkeneoAttributeProcessor implements AkeneoAttributeProcessorInterface
{
    public static function getDefaultPriority(): int
    {
        return 0;
    }

    protected function getSetterMethodFromAttributeCode(string $attributeCode): string
    {
        return $this->camelCaseToSnakeCaseNameConverter->denormalize(sprintf(
            'set%s%s',
            ucfirst($attributeCode),
            self::CUSTOM_PROPERTIES_SUFFIX
        ));
    }

    protected function setValueToMethod(
        ResourceInterface $model,
        string $attributeCode,
        array $translations,
        string $locale,
        string $scope
    ): void {
        if (!$model instanceof ProductVariantInterface) {
            return;
        }

        $attributeValueValue = $this->akeneoAttributeDataProvider->getData(
            $attributeCode,
            $translations,
            $locale,
            $scope
        );

        $translationModel = $model->getTranslation($locale);
        $reflectionMethod = new ReflectionMethod(
            $translationModel,
            $this->getSetterMethodFromAttributeCode($attributeCode)
        );
        $reflectionMethod->invoke($translationModel, $attributeValueValue);
    }
}
