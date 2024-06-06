<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Data;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias]
final class AkeneoAttributePropertiesProvider implements AkeneoAttributePropertiesProviderInterface
{
    private bool $loadsAllAttributesAtOnce = false;

    private array $attributes = [];

    public function __construct(private AkeneoPimClientInterface $client)
    {
    }

    public function setLoadsAllAttributesAtOnce(bool $loadsAllAttributesAtOnce): self
    {
        $this->loadsAllAttributesAtOnce = $loadsAllAttributesAtOnce;

        return $this;
    }

    public function isLocalizable(string $attributeCode): bool
    {
        return isset($this->getProperties($attributeCode)['localizable']) && (bool) $this->getProperties($attributeCode)['localizable'];
    }

    public function getProperties(string $attributeCode): array
    {
        if (isset($this->attributes[$attributeCode])) {
            return $this->attributes[$attributeCode];
        }

        if ($this->loadsAllAttributesAtOnce) {
            foreach ($this->client->getAttributeApi()->all() as $attributeResource) {
                $this->attributes[$attributeResource['code']] = $attributeResource;
            }
        }

        if (!isset($this->attributes[$attributeCode]) && !$this->loadsAllAttributesAtOnce) {
            $this->attributes[$attributeCode] = $this->client->getAttributeApi()->get($attributeCode);
        }

        return $this->attributes[$attributeCode];
    }

    public function isUnique(string $attributeCode): bool
    {
        return isset($this->getProperties($attributeCode)['unique']) && (bool) $this->getProperties($attributeCode)['unique'];
    }

    public function isScopable(string $attributeCode): bool
    {
        return isset($this->getProperties($attributeCode)['scopable']) && (bool) $this->getProperties($attributeCode)['scopable'];
    }

    public function getLabel(string $attributeCode, ?string $locale): string
    {
        $labels = $this->getLabels($attributeCode);
        if (null === $locale || !isset($labels[$locale])) {
            return current($labels);
        }

        return $labels[$locale];
    }

    public function getLabels(string $attributeCode): array
    {
        return $this->getProperties($attributeCode)['labels'] ?? [];
    }

    public function getType(string $attributeCode): string
    {
        return $this->getProperties($attributeCode)['type'];
    }
}
