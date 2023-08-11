<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Synolia\SyliusAkeneoPlugin\Component\Cache\CacheKey;

final class AkeneoAttributePropertiesProvider
{
    private array $attributes = [];

    private array $attributeIsUnique = [];

    private array $attributeIsScopable = [];

    private array $attributeLabel = [];

    private array $attributeLabels = [];

    private array $attributeType = [];

    private array $attributeIsLocalizable = [];

    private bool $loadsAllAttributesAtOnce = true;

    public function __construct(
        private AkeneoPimClientInterface $client,
        private CacheInterface $akeneoAttributes,
    ) {
    }

    public function setLoadsAllAttributesAtOnce(bool $loadsAllAttributesAtOnce): self
    {
        $this->loadsAllAttributesAtOnce = $loadsAllAttributesAtOnce;

        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getProperties(string $attributeCode): array
    {
        if (array_key_exists($attributeCode, $this->attributes)) {
            return $this->attributes[$attributeCode];
        }

        if (!$this->loadsAllAttributesAtOnce) {
            return $this->attributes[$attributeCode] = $this->akeneoAttributes->get(sprintf(CacheKey::ATTRIBUTES_PROPERTIES, $attributeCode), function () use ($attributeCode): array {
                return $this->client->getAttributeApi()->get($attributeCode);
            });
        }

        if ($this->loadsAllAttributesAtOnce) {
            $this->attributes = $this->akeneoAttributes->get(sprintf(CacheKey::ATTRIBUTES_PROPERTIES, $attributeCode), function () use ($attributeCode): array {
                $attributes = $this->client->getAttributeApi()->all();

                $storedAttributes = [];
                foreach ($attributes as $attribute) {
                    $storedAttributes[$attribute['code']] = $attribute;
                }

                return $storedAttributes;
            });

            return $this->attributes[$attributeCode];
        }

        if (!array_key_exists($attributeCode, $this->attributes)) {
            return $this->attributes[$attributeCode] = $this->client->getAttributeApi()->get($attributeCode);
        }

        return $this->attributes[$attributeCode];
    }

    public function isUnique(string $attributeCode): bool
    {
        if (array_key_exists($attributeCode, $this->attributeIsUnique)) {
            return $this->attributeIsUnique[$attributeCode];
        }

        return $this->attributeIsUnique[$attributeCode] = $this->akeneoAttributes->get(\sprintf(CacheKey::ATTRIBUTES_UNIQUE, $attributeCode), function () use ($attributeCode): bool {
            return isset($this->getProperties($attributeCode)['unique']) && (bool) $this->getProperties($attributeCode)['unique'];
        });
    }

    public function isScopable(string $attributeCode): bool
    {
        if (array_key_exists($attributeCode, $this->attributeIsScopable)) {
            return $this->attributeIsScopable[$attributeCode];
        }

        return $this->attributeIsScopable[$attributeCode] = $this->akeneoAttributes->get(\sprintf(CacheKey::ATTRIBUTES_SCOPABLE, $attributeCode), function () use ($attributeCode): bool {
            return isset($this->getProperties($attributeCode)['scopable']) && (bool) $this->getProperties($attributeCode)['scopable'];
        });
    }

    public function getLabel(string $attributeCode, ?string $locale): string
    {
        if (array_key_exists($attributeCode, $this->attributeLabel)) {
            return $this->attributeLabel[$attributeCode];
        }

        return $this->attributeLabel[$attributeCode] = $this->akeneoAttributes->get(\sprintf(CacheKey::ATTRIBUTES_LABEL, $attributeCode, $locale), function () use ($attributeCode, $locale): string {
            $labels = $this->getLabels($attributeCode);
            if (null === $locale || !isset($labels[$locale])) {
                return current($labels);
            }

            return $labels[$locale];
        });
    }

    public function getLabels(string $attributeCode): array
    {
        if (array_key_exists($attributeCode, $this->attributeLabels)) {
            return $this->attributeLabels[$attributeCode];
        }

        return $this->attributeLabels[$attributeCode] = $this->akeneoAttributes->get(\sprintf(CacheKey::ATTRIBUTES_LABELS, $attributeCode), function () use ($attributeCode): array {
            return $this->getProperties($attributeCode)['labels'] ?? [];
        });
    }

    public function getType(string $attributeCode): string
    {
        if (array_key_exists($attributeCode, $this->attributeType)) {
            return $this->attributeType[$attributeCode];
        }

        return $this->attributeType[$attributeCode] = $this->akeneoAttributes->get(\sprintf(CacheKey::ATTRIBUTES_TYPE, $attributeCode), function () use ($attributeCode): string {
            return $this->getProperties($attributeCode)['type'];
        });
    }

    public function isLocalizable(string $attributeCode): bool
    {
        if (array_key_exists($attributeCode, $this->attributeIsLocalizable)) {
            return $this->attributeIsLocalizable[$attributeCode];
        }

        return $this->attributeIsLocalizable[$attributeCode] = $this->akeneoAttributes->get(\sprintf(CacheKey::ATTRIBUTES_LOCALIZABLE, $attributeCode), function () use ($attributeCode): bool {
            return isset($this->getProperties($attributeCode)['localizable']) && (bool) $this->getProperties($attributeCode)['localizable'];
        });
    }
}
