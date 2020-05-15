<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;

final class AkeneoAttributePropertiesProvider
{
    /** @var bool */
    private $loadsAllAttributesAtOnce = false;

    /** @var array */
    private $attributes = [];

    /** @var \Akeneo\Pim\ApiClient\AkeneoPimClientInterface */
    private $client;

    public function __construct(AkeneoPimClientInterface $akeneoPimClient)
    {
        $this->client = $akeneoPimClient;
    }

    public function setLoadsAllAttributesAtOnce(bool $loadsAllAttributesAtOnce): self
    {
        $this->loadsAllAttributesAtOnce = $loadsAllAttributesAtOnce;

        return $this;
    }

    public function isLocalizable(string $attributeCode): bool
    {
        return (isset($this->getProperties($attributeCode)['localizable'])) ? (bool) $this->getProperties($attributeCode)['localizable'] : false;
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
        return (isset($this->getProperties($attributeCode)['unique'])) ? (bool) $this->getProperties($attributeCode)['unique'] : false;
    }

    public function isScopable(string $attributeCode): bool
    {
        return (isset($this->getProperties($attributeCode)['scopable'])) ? (bool) $this->getProperties($attributeCode)['scopable'] : false;
    }

    public function getLabel(string $attributeCode, ?string $locale): string
    {
        $labels = $this->getLabels($attributeCode);
        if (null === $locale || !isset($labels[$locale])) {
            return \current($labels);
        }

        return $labels[$locale];
    }

    public function getLabels(string $attributeCode): array
    {
        return (isset($this->getProperties($attributeCode)['labels'])) ? $this->getProperties($attributeCode)['labels'] : [];
    }

    public function getType(string $attributeCode): string
    {
        return $this->getProperties($attributeCode)['type'];
    }
}
