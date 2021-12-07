<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;

final class AkeneoReferenceEntityAttributePropertiesProvider
{
    private bool $loadsAllAttributesAtOnce = false;

    private array $attributes = [];

    private AkeneoPimEnterpriseClientInterface $client;

    public function __construct(AkeneoPimEnterpriseClientInterface $akeneoPimClient)
    {
        $this->client = $akeneoPimClient;
    }

    public function setLoadsAllAttributesAtOnce(bool $loadsAllAttributesAtOnce): self
    {
        $this->loadsAllAttributesAtOnce = $loadsAllAttributesAtOnce;

        return $this;
    }

    public function isLocalizable(string $referenceEntityCode, string $referenceEntityAttributeCode): bool
    {
        return isset($this->getProperties($referenceEntityCode, $referenceEntityAttributeCode)['value_per_locale']) && (bool) $this->getProperties($referenceEntityCode, $referenceEntityAttributeCode)['value_per_locale'];
    }

    public function getProperties(string $referenceEntityCode, string $referenceEntityAttributeCode): array
    {
        if (isset($this->attributes[$referenceEntityCode][$referenceEntityAttributeCode])) {
            return $this->attributes[$referenceEntityCode][$referenceEntityAttributeCode];
        }

        if ($this->loadsAllAttributesAtOnce) {
            foreach ($this->client->getReferenceEntityAttributeApi()->all($referenceEntityCode) as $attributeResource) {
                $this->attributes[$referenceEntityCode][$attributeResource['code']] = $attributeResource;
            }
        }

        if (!isset($this->attributes[$referenceEntityCode][$referenceEntityAttributeCode]) && !$this->loadsAllAttributesAtOnce) {
            $this->attributes[$referenceEntityCode][$referenceEntityAttributeCode] = $this->client->getReferenceEntityAttributeApi()->get($referenceEntityCode, $referenceEntityAttributeCode);
        }

        return $this->attributes[$referenceEntityCode][$referenceEntityAttributeCode];
    }

    public function isUnique(string $referenceEntityCode, string $referenceEntityAttributeCode): bool
    {
        return isset($this->getProperties($referenceEntityCode, $referenceEntityAttributeCode)['unique']) && (bool) $this->getProperties($referenceEntityCode, $referenceEntityAttributeCode)['unique'];
    }

    public function isScopable(string $referenceEntityCode, string $referenceEntityAttributeCode): bool
    {
        return isset($this->getProperties($referenceEntityCode, $referenceEntityAttributeCode)['value_per_channel']) && (bool) $this->getProperties($referenceEntityCode, $referenceEntityAttributeCode)['value_per_channel'];
    }

    public function getLabel(string $referenceEntityCode, string $referenceEntityAttributeCode, ?string $locale): string
    {
        $labels = $this->getLabels($referenceEntityCode, $referenceEntityAttributeCode);
        if (null === $locale || !isset($labels[$locale])) {
            return current($labels);
        }

        return $labels[$locale];
    }

    public function getLabels(string $referenceEntityCode, string $referenceEntityAttributeCode): array
    {
        return (isset($this->getProperties($referenceEntityCode, $referenceEntityAttributeCode)['labels'])) ? $this->getProperties($referenceEntityCode, $referenceEntityAttributeCode)['labels'] : [];
    }

    public function getType(string $referenceEntityCode, string $referenceEntityAttributeCode): string
    {
        return $this->getProperties($referenceEntityCode, $referenceEntityAttributeCode)['type'];
    }
}
