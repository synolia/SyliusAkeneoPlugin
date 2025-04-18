<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Asset;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;

final class AkeneoAssetAttributePropertiesProvider implements AkeneoAssetAttributePropertiesProviderInterface
{
    private bool $loadsAllAttributesAtOnce = true;

    private array $attributes = [];

    public function __construct(private AkeneoPimClientInterface $client)
    {
    }

    public function setLoadsAllAttributesAtOnce(bool $loadsAllAttributesAtOnce): self
    {
        $this->loadsAllAttributesAtOnce = $loadsAllAttributesAtOnce;

        return $this;
    }

    public function isLocalizable(string $assetFamilyCode, string $attributeCode): bool
    {
        return isset($this->getProperties($assetFamilyCode, $attributeCode)['value_per_locale']) && (bool) $this->getProperties($assetFamilyCode, $attributeCode)['value_per_locale'];
    }

    public function getProperties(string $assetFamilyCode, string $attributeCode): array
    {
        if (isset($this->attributes[$assetFamilyCode][$attributeCode])) {
            return $this->attributes[$assetFamilyCode][$attributeCode];
        }

        if ($this->loadsAllAttributesAtOnce) {
            foreach ($this->client->getAssetAttributeApi()->all($assetFamilyCode) as $attributeResource) {
                $this->attributes[$assetFamilyCode][$attributeResource['code']] = $attributeResource;
            }
        }

        if (!isset($this->attributes[$assetFamilyCode][$attributeCode]) && !$this->loadsAllAttributesAtOnce) {
            $this->attributes[$assetFamilyCode][$attributeCode] = $this->client->getAssetAttributeApi()->get($assetFamilyCode, $attributeCode);
        }

        return $this->attributes[$assetFamilyCode][$attributeCode];
    }

    public function isScopable(string $assetFamilyCode, string $attributeCode): bool
    {
        return isset($this->getProperties($assetFamilyCode, $attributeCode)['value_per_channel']) && (bool) $this->getProperties($assetFamilyCode, $attributeCode)['value_per_channel'];
    }

    public function getLabel(string $assetFamilyCode, string $attributeCode, ?string $locale): string
    {
        $labels = $this->getLabels($assetFamilyCode, $attributeCode);
        if (null === $locale || !isset($labels[$locale])) {
            return \current($labels);
        }

        return $labels[$locale];
    }

    public function getLabels(string $assetFamilyCode, string $attributeCode): array
    {
        return $this->getProperties($assetFamilyCode, $attributeCode)['labels'] ?? [];
    }

    public function getType(string $assetFamilyCode, string $attributeCode): string
    {
        return $this->getProperties($assetFamilyCode, $attributeCode)['type'];
    }
}
