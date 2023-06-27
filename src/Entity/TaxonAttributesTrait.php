<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Webmozart\Assert\Assert;

trait TaxonAttributesTrait
{
    /** @ORM\OneToMany(
     *     targetEntity=\Synolia\SyliusAkeneoPlugin\Entity\TaxonAttributeValue::class,
     *     mappedBy="subject",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    #[ORM\OneToMany(
        targetEntity: TaxonAttributeValue::class,
        mappedBy: 'subject',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    public Collection $attributes;

    public function __construct()
    {
        $this->attributes = new ArrayCollection();
    }

    /**
     * @return Collection|TaxonAttributeValueInterface[]
     */
    public function getAttributes(): Collection
    {
        return $this->attributes;
    }

    public function getAttributesByLocale(
        string $localeCode,
        string $fallbackLocaleCode,
        ?string $baseLocaleCode = null,
    ): Collection {
        if (null === $baseLocaleCode || $baseLocaleCode === $fallbackLocaleCode) {
            $baseLocaleCode = $fallbackLocaleCode;
            $fallbackLocaleCode = null;
        }

        $attributes = $this->attributes->filter(
            function (ProductAttributeValueInterface $attribute) use ($baseLocaleCode) {
                return $attribute->getLocaleCode() === $baseLocaleCode || null === $attribute->getLocaleCode();
            },
        );

        $attributesWithFallback = [];
        foreach ($attributes as $attribute) {
            $attributesWithFallback[] = $this->getAttributeInDifferentLocale($attribute, $localeCode, $fallbackLocaleCode);
        }

        return new ArrayCollection($attributesWithFallback);
    }

    public function addAttribute(?TaxonAttributeValueInterface $attribute): void
    {
        Assert::isInstanceOf(
            $attribute,
            TaxonAttributeValue::class,
            'Attribute objects added to a Product object have to implement ProductAttributeValueInterface',
        );

        if (!$this->hasAttribute($attribute)) {
            $attribute->setTaxon($this);
            $this->attributes->add($attribute);
        }
    }

    public function removeAttribute(?TaxonAttributeValueInterface $attribute): void
    {
        Assert::isInstanceOf(
            $attribute,
            TaxonAttributeValue::class,
            'Attribute objects removed from a Product object have to implement ProductAttributeValueInterface',
        );

        if ($this->hasAttribute($attribute)) {
            $this->attributes->removeElement($attribute);
            $attribute->setTaxon(null);
        }
    }

    public function hasAttribute(TaxonAttributeValueInterface $attribute): bool
    {
        return $this->attributes->contains($attribute);
    }

    public function hasAttributeByCodeAndLocale(string $attributeCode, ?string $localeCode = null): bool
    {
        return $this->getAttributeByCodeAndLocale($attributeCode, $localeCode) !== null;
    }

    public function getAttributeByCodeAndLocale(
        string $attributeCode,
        ?string $localeCode = null,
    ): ?TaxonAttributeValueInterface {
        $localeCode = $localeCode ?: $this->getTranslation()->getLocale();

        foreach ($this->getAttributes() as $attribute) {
            if ($attribute->getAttribute()->getCode() === $attributeCode &&
                ($attribute->getLocaleCode() === $localeCode || null === $attribute->getLocaleCode())) {
                return $attribute;
            }
        }

        return null;
    }

    protected function getAttributeInDifferentLocale(
        TaxonAttributeValueInterface $attributeValue,
        string $localeCode,
        ?string $fallbackLocaleCode = null,
    ): TaxonAttributeValueInterface {
        if (!$this->hasNotEmptyAttributeByCodeAndLocale($attributeValue->getCode(), $localeCode)) {
            if (
                null !== $fallbackLocaleCode &&
                $this->hasNotEmptyAttributeByCodeAndLocale($attributeValue->getCode(), $fallbackLocaleCode)
            ) {
                return $this->getAttributeByCodeAndLocale($attributeValue->getCode(), $fallbackLocaleCode);
            }

            return $attributeValue;
        }

        return $this->getAttributeByCodeAndLocale($attributeValue->getCode(), $localeCode);
    }

    protected function hasNotEmptyAttributeByCodeAndLocale(string $attributeCode, string $localeCode): bool
    {
        $attributeValue = $this->getAttributeByCodeAndLocale($attributeCode, $localeCode);
        if (null === $attributeValue) {
            return false;
        }

        $value = $attributeValue->getValue();
        if ('' === $value || null === $value || [] === $value) {
            return false;
        }

        return true;
    }
}
