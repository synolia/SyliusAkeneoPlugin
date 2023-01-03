<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Component\Attribute\AttributeType;

use Sylius\Component\Attribute\AttributeType\AttributeTypeInterface;
use Sylius\Component\Attribute\Model\AttributeValueInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class ReferenceEntityAttributeType implements AttributeTypeInterface
{
    public const TYPE = 'reference_entity';

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
        return static::TYPE;
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
