<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Data;

interface AkeneoReferenceEntityAttributePropertiesProviderInterface
{
    public function isLocalizable(string $referenceEntityCode, string $referenceEntityAttributeCode): bool;

    public function getProperties(string $referenceEntityCode, string $referenceEntityAttributeCode): array;

    public function isUnique(string $referenceEntityCode, string $referenceEntityAttributeCode): bool;

    public function isScopable(string $referenceEntityCode, string $referenceEntityAttributeCode): bool;

    public function getLabel(
        string $referenceEntityCode,
        string $referenceEntityAttributeCode,
        ?string $locale,
    ): string;

    public function getLabels(string $referenceEntityCode, string $referenceEntityAttributeCode): array;

    public function getType(string $referenceEntityCode, string $referenceEntityAttributeCode): string;
}
