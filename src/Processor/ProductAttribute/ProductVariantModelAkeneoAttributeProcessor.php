<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute;

use ReflectionMethod;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

final class ProductVariantModelAkeneoAttributeProcessor extends AbstractModelAkeneoAttributeProcessor implements AkeneoAttributeProcessorInterface
{
    private const NATIVE_PROPERTIES = [
        'on_hold',
        'on_hand',
        'tracked',
        'width',
        'height',
        'enabled',
        'shipping_required',
    ];

    public static function getDefaultPriority(): int
    {
        return 100;
    }

    protected function getSetterMethodFromAttributeCode(string $attributeCode): string
    {
        if (\in_array($this->camelCaseToSnakeCaseNameConverter->normalize($attributeCode), self::NATIVE_PROPERTIES) ||
            \in_array($this->camelCaseToSnakeCaseNameConverter->denormalize($attributeCode), self::NATIVE_PROPERTIES)
        ) {
            return $this->camelCaseToSnakeCaseNameConverter->denormalize(sprintf(
                'set%s',
                ucfirst($attributeCode)
            ));
        }

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

        $reflectionMethod = new ReflectionMethod(
            $model,
            $this->getSetterMethodFromAttributeCode($attributeCode)
        );
        $reflectionMethod->invoke($model, $attributeValueValue);
    }
}
