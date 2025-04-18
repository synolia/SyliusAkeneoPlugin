<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute;

use Psr\Log\LoggerInterface;
use ReflectionMethod;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Synolia\SyliusAkeneoPlugin\Provider\Data\AkeneoAttributeDataProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Data\AkeneoAttributePropertiesProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;

final class ProductVariantTranslationModelAkeneoAttributeProcessor extends AbstractModelAkeneoAttributeProcessor implements AkeneoAttributeProcessorInterface
{
    public function __construct(
        #[Autowire('@serializer.name_converter.camel_case_to_snake_case')]
        CamelCaseToSnakeCaseNameConverter $camelCaseToSnakeCaseNameConverter,
        AkeneoAttributePropertiesProviderInterface $akeneoAttributePropertyProvider,
        AkeneoAttributeDataProviderInterface $akeneoAttributeDataProvider,
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        LoggerInterface $akeneoLogger,
        #[Autowire('%sylius.model.product_variant_translation.class%')]
        string $model,
    ) {
        parent::__construct($camelCaseToSnakeCaseNameConverter, $akeneoAttributePropertyProvider, $akeneoAttributeDataProvider, $syliusAkeneoLocaleCodeProvider, $akeneoLogger, $model);
    }

    public static function getDefaultPriority(): int
    {
        return 0;
    }

    protected function getSetterMethodFromAttributeCode(string $attributeCode): string
    {
        return $this->camelCaseToSnakeCaseNameConverter->denormalize(sprintf(
            'set%s%s',
            ucfirst($attributeCode),
            self::CUSTOM_PROPERTIES_SUFFIX,
        ));
    }

    protected function setValueToMethod(
        ResourceInterface $model,
        string $attributeCode,
        array $translations,
        string $locale,
        string $scope,
    ): void {
        if (!$model instanceof ProductVariantInterface) {
            return;
        }

        $attributeValueValue = $this->akeneoAttributeDataProvider->getData(
            $attributeCode,
            $translations,
            $locale,
            $scope,
        );

        $translationModel = $model->getTranslation($locale);
        $reflectionMethod = new ReflectionMethod(
            $translationModel,
            $this->getSetterMethodFromAttributeCode($attributeCode),
        );
        $reflectionMethod->invoke($translationModel, $attributeValueValue);
    }
}
