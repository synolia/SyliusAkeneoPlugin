<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\Provider\ConfigurationProvider;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Transformer\AttributeOptionValueDataTransformerInterface;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\MultiSelectAttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\SelectAttributeTypeMatcher;

class ProductAttributeChoiceProcessor implements ProductAttributeChoiceProcessorInterface
{
    /** @var \Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface */
    private $client;

    /** @var \Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher */
    private $attributeTypeMatcher;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var \Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider */
    private $syliusAkeneoLocaleCodeProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\ConfigurationProvider */
    private $configurationProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\Transformer\AttributeOptionValueDataTransformerInterface */
    private $attributeOptionValueDataTransformer;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    public function __construct(
        ClientFactoryInterface $clientFactory,
        AttributeTypeMatcher $attributeTypeMatcher,
        LoggerInterface $akeneoLogger,
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        ConfigurationProvider $configurationProvider,
        AttributeOptionValueDataTransformerInterface $attributeOptionValueDataTransformer,
        EntityManagerInterface $entityManager
    ) {
        $this->client = $clientFactory->createFromApiCredentials();
        $this->attributeTypeMatcher = $attributeTypeMatcher;
        $this->logger = $akeneoLogger;
        $this->syliusAkeneoLocaleCodeProvider = $syliusAkeneoLocaleCodeProvider;
        $this->configurationProvider = $configurationProvider;
        $this->attributeOptionValueDataTransformer = $attributeOptionValueDataTransformer;
        $this->entityManager = $entityManager;
    }

    public function process(
        AttributeInterface $attribute,
        array $resource
    ): void {
        try {
            $attributeTypeMatcher = $this->attributeTypeMatcher->match($resource['type']);

            if (
                !$attributeTypeMatcher instanceof SelectAttributeTypeMatcher &&
                !$attributeTypeMatcher instanceof MultiSelectAttributeTypeMatcher
            ) {
                return;
            }

            $this->setAttributeChoices(
                $attribute,
                $this->client->getAttributeOptionApi()->all(
                    $resource['code'],
                    $this->configurationProvider->getConfiguration()->getPaginationSize()
                ),
                $attributeTypeMatcher instanceof MultiSelectAttributeTypeMatcher
            );
        } catch (UnsupportedAttributeTypeException $unsupportedAttributeTypeException) {
            $this->logger->warning(\sprintf(
                '%s: %s',
                $resource['code'],
                $unsupportedAttributeTypeException->getMessage()
            ));

            return;
        }
    }

    private function setAttributeChoices(
        AttributeInterface $attribute,
        iterable $options,
        bool $isMultiple
    ): void {
        $choices = [];
        foreach ($options as $option) {
            $transformedCode = $this->attributeOptionValueDataTransformer->transform($option['code']);
            foreach ($option['labels'] as $locale => $label) {
                if (!in_array($locale, $this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms(), true)) {
                    continue;
                }
                if (!isset($choices[$transformedCode]) && [] !== $this->getUnusedLocale($option['labels'])) {
                    $choices[$transformedCode] = $this->getUnusedLocale($option['labels']);
                }
                $choices[$transformedCode][$locale] = $label;
            }
        }

        if ([] === $choices) {
            $this->entityManager->remove($attribute);

            return;
        }

        $attribute->setConfiguration([
            'choices' => $choices,
            'multiple' => $isMultiple,
            'min' => null,
            'max' => null,
        ]);
    }

    private function getUnusedLocale(array $labels): array
    {
        $localeDiff = array_diff($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms(), array_keys($labels));
        if ([] === $localeDiff) {
            return [];
        }

        foreach ($localeDiff as $locale) {
            $localeUnused[$locale] = ' ';
        }

        return $localeUnused;
    }
}
