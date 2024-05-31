<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

interface AkeneoAttributePropertiesProviderInterface
{
    public function isLocalizable(string $attributeCode): bool;

    public function getProperties(string $attributeCode): array;

    public function isUnique(string $attributeCode): bool;

    public function isScopable(string $attributeCode): bool;

    public function getLabel(string $attributeCode, ?string $locale): string;

    public function getLabels(string $attributeCode): array;

    public function getType(string $attributeCode): string;
}
