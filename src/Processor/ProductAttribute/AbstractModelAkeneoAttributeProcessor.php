<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute;

use Psr\Log\LoggerInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeDataProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;

abstract class AbstractModelAkeneoAttributeProcessor
{
    protected const CUSTOM_PROPERTIES_SUFFIX = 'AkeneoAttribute';

    /** @var \Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter */
    protected $camelCaseToSnakeCaseNameConverter;

    /** @var AkeneoAttributeDataProviderInterface */
    protected $akeneoAttributeDataProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider */
    protected $syliusAkeneoLocaleCodeProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider */
    protected $akeneoAttributePropertyProvider;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var string */
    protected $model;

    public function __construct(
        CamelCaseToSnakeCaseNameConverter $camelCaseToSnakeCaseNameConverter,
        AkeneoAttributePropertiesProvider $akeneoAttributePropertyProvider,
        AkeneoAttributeDataProviderInterface $akeneoAttributeDataProvider,
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        LoggerInterface $akeneoLogger,
        string $model
    ) {
        $this->camelCaseToSnakeCaseNameConverter = $camelCaseToSnakeCaseNameConverter;
        $this->akeneoAttributePropertyProvider = $akeneoAttributePropertyProvider;
        $this->akeneoAttributeDataProvider = $akeneoAttributeDataProvider;
        $this->syliusAkeneoLocaleCodeProvider = $syliusAkeneoLocaleCodeProvider;
        $this->logger = $akeneoLogger;
        $this->model = $model;
    }

    public function process(string $attributeCode, array $context = []): void
    {
        $this->logger->debug(\sprintf(
            'Attribute "%s" is beeing processed by "%s"',
            $attributeCode,
            static::class
        ));

        foreach ($context['data'] as $translation) {
            if (null !== $translation['locale']
                && false === $this->syliusAkeneoLocaleCodeProvider->isActiveLocale($translation['locale'])) {
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
        return \method_exists(
            $this->model,
            $this->getSetterMethodFromAttributeCode($attributeCode)
        );
    }

    abstract protected function getSetterMethodFromAttributeCode(string $attributeCode): string;

    abstract protected function setValueToMethod(
        ResourceInterface $model,
        string $attributeCode,
        array $translations,
        string $locale,
        string $scope
    ): void;
}
