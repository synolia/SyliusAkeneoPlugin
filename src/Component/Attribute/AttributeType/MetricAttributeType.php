<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Component\Attribute\AttributeType;

use Sylius\Component\Attribute\AttributeType\AttributeTypeInterface;
use Sylius\Component\Attribute\Model\AttributeValueInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Synolia\SyliusAkeneoPlugin\Component\Attribute\AttributeType\Configuration\MetricConfigurationAttributeType as ConfigurationFormType;
use Synolia\SyliusAkeneoPlugin\Form\Type\AttributeType\MetricAttributeType as FormType;

#[AsAlias(id: 'sylius.attribute_type.metric')]
#[AutoconfigureTag(
    name: 'sylius.attribute.type',
    attributes: [
        'attribute_type' => self::TYPE,
        'label' => 'json',
        'form_type' => FormType::class,
        'configuration_form_type' => ConfigurationFormType::class,
    ],
)]
final class MetricAttributeType implements AttributeTypeInterface
{
    public const TYPE = 'metric';

    /**
     * {@inheritdoc}
     */
    public function getStorageType(): string
    {
        return AttributeValueInterface::STORAGE_JSON;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return self::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(
        AttributeValueInterface $attributeValue,
        ExecutionContextInterface $context,
        array $configuration,
    ): void {
    }
}
