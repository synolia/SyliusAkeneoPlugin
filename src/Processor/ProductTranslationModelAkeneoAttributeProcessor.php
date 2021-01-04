<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor;

use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeDataProvider;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;

class ProductTranslationModelAkeneoAttributeProcessor extends AbstractModelAkeneoAttributeProcessor implements AkeneoAttributeProcessorInterface
{
    private const NATIVE_PROPERTIES = ['slug', 'description', 'short_description', 'meta_description', 'meta_keywords'];

    public function __construct(
        CamelCaseToSnakeCaseNameConverter $camelCaseToSnakeCaseNameConverter,
        AkeneoAttributePropertiesProvider $akeneoAttributePropertyProvider,
        AkeneoAttributeDataProvider $akeneoAttributeDataProvider,
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
        return 200;
    }

    public function support(string $attributeCode, array $context = []): bool
    {
        return \method_exists(
            $this->model,
            $this->getSetterMethodFromAttributeCode($attributeCode)
        ) && $context['model'] instanceof ProductInterface;
    }

    protected function getSetterMethodFromAttributeCode(string $attributeCode): string
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
            'set%s%s',
            \ucfirst($attributeCode),
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

        $translationModel = $model->getTranslation($locale);
        $reflectionMethod = new \ReflectionMethod(
            $translationModel,
            $this->getSetterMethodFromAttributeCode($attributeCode)
        );
        $reflectionMethod->invoke($translationModel, $attributeValueValue);
    }
}
