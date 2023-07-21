<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Component\TaxonAttribute\Model;

use Doctrine\Common\Collections\Collection;
use Synolia\SyliusAkeneoPlugin\Entity\TaxonAttributeValue;
use Synolia\SyliusAkeneoPlugin\Entity\TaxonAttributeValueInterface;

interface TaxonAttributeSubjectInterface
{
    /**
     * @return Collection|TaxonAttributeValueInterface[]
     *
     * @psalm-return Collection<array-key, TaxonAttributeValue>
     */
    public function getAttributes(): Collection;

    /**
     * @return Collection|TaxonAttributeValueInterface[]
     *
     * @psalm-return Collection<array-key, TaxonAttributeValue>
     */
    public function getAttributesByLocale(
        string $localeCode,
        string $fallbackLocaleCode,
        ?string $baseLocaleCode = null,
    ): Collection;

    public function addAttribute(TaxonAttributeValueInterface $attribute): void;

    public function removeAttribute(TaxonAttributeValueInterface $attribute): void;

    public function hasAttribute(TaxonAttributeValueInterface $attribute): bool;

    public function hasAttributeByCodeAndLocale(string $attributeCode, ?string $localeCode = null): bool;

    public function getAttributeByCodeAndLocale(
        string $attributeCode,
        ?string $localeCode = null,
    ): ?TaxonAttributeValueInterface;
}
