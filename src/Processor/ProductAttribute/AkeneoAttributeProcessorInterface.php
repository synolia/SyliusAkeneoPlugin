<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute;

interface AkeneoAttributeProcessorInterface
{
    public const TAG_ID = 'sylius.akeneo.attribute_processor';

    public static function getDefaultPriority(): int;

    public function support(string $attributeCode, array $context = []): bool;

    public function process(string $attributeCode, array $context = []): void;
}
