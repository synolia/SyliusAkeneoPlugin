<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Synolia\SyliusAkeneoPlugin\Retriever\FamilyRetrieverInterface;

final class AkeneoFamilyPropertiesProvider implements AkeneoFamilyPropertiesProviderInterface
{
    public function __construct(
        private FamilyRetrieverInterface $familiesRetriever,
    ) {
    }

    public function getProperties(string $familyCode): array
    {
        return $this->familiesRetriever->getFamily($familyCode);
    }
}
