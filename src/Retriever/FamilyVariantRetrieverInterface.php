<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Retriever;

interface FamilyVariantRetrieverInterface
{
    public function getVariants(string $familyCode): array;

    public function getVariant(string $familyCode, string $familyVariantCode): array;
}
