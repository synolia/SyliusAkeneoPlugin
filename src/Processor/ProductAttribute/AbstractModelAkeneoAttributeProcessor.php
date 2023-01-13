<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute;

use Psr\Log\LoggerInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeDataProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;

abstract class AbstractModelAkeneoAttributeProcessor
{
    protected const CUSTOM_PROPERTIES_SUFFIX = 'AkeneoAttribute';

    public function __construct(
        protected CamelCaseToSnakeCaseNameConverter $camelCaseToSnakeCaseNameConverter,
        protected AkeneoAttributePropertiesProvider $akeneoAttributePropertyProvider,
        protected AkeneoAttributeDataProviderInterface $akeneoAttributeDataProvider,
        protected SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        protected LoggerInterface $logger,
        protected string $model,
    ) {
    }

    public function process(string $attributeCode, array $context = []): void
    {
        $this->logger->debug(sprintf(
            'Attribute "%s" is beeing processed by "%s"',
            $attributeCode,
            static::class,
        ));

        foreach ($context['data'] as $translation) {
            if (null !== $translation['locale'] &&
                !$this->syliusAkeneoLocaleCodeProvider->isActiveLocale($translation['locale'])) {
                continue;
            }

            if (null === $translation['locale']) {
                foreach ($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms() as $locale) {
                    $this->setValueToMethod($context['model'], $attributeCode, $context['data'], $locale, $context['scope']);
                }

                continue;
            }

            $this->setValueToMethod($context['model'], $attributeCode, $context['data'], $translation['locale'], $context['scope']);
        }
    }

    /**
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function support(string $attributeCode, array $context = []): bool
    {
        return method_exists(
            $this->model,
            $this->getSetterMethodFromAttributeCode($attributeCode),
        );
    }

    abstract protected function getSetterMethodFromAttributeCode(string $attributeCode): string;

    abstract protected function setValueToMethod(
        ResourceInterface $model,
        string $attributeCode,
        array $translations,
        string $locale,
        string $scope,
    ): void;
}
