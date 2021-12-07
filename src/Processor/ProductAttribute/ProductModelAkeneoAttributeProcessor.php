<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute;

use ReflectionMethod;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeDataProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;

final class ProductModelAkeneoAttributeProcessor extends AbstractModelAkeneoAttributeProcessor implements AkeneoAttributeProcessorInterface
{
    public function __construct(
        CamelCaseToSnakeCaseNameConverter $camelCaseToSnakeCaseNameConverter,
        AkeneoAttributePropertiesProvider $akeneoAttributePropertyProvider,
        AkeneoAttributeDataProviderInterface $akeneoAttributeDataProvider,
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        LoggerInterface $akeneoLogger,
        string $model
    ) {
        parent::__construct(
            $camelCaseToSnakeCaseNameConverter,
            $akeneoAttributePropertyProvider,
            $akeneoAttributeDataProvider,
            $syliusAkeneoLocaleCodeProvider,
            $akeneoLogger,
            $model
        );
    }

    public static function getDefaultPriority(): int
    {
        return 300;
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
        if (!$model instanceof ProductInterface) {
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
