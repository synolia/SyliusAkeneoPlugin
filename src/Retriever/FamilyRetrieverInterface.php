<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Retriever;

interface FamilyRetrieverInterface
{
    public function getFamilyCodeByVariantCode(string $familyVariantCode): string;

    public function getFamily(string $familyCode): array;
}
