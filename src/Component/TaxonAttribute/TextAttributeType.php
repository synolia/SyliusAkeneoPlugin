<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Component\TaxonAttribute;

use Sylius\Component\Attribute\AttributeType\AttributeTypeInterface;
use Sylius\Component\Attribute\Model\AttributeValueInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class TextAttributeType implements AttributeTypeInterface
{
    public const TYPE = 'text';

    /**
     * {@inheritdoc}
     */
    public function getStorageType(): string
    {
        return AttributeValueInterface::STORAGE_TEXT;
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
