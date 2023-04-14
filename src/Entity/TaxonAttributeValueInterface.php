<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Sylius\Component\Core\Model\TaxonInterface;

interface TaxonAttributeValueInterface
{
    public function getSubject(): ?TaxonInterface;

    public function setSubject(?TaxonInterface $subject): void;

    public function getAttribute(): ?TaxonAttributeInterface;

    public function setAttribute(?TaxonAttributeInterface $attribute): void;

    public function getLocaleCode(): ?string;

    public function setLocaleCode(?string $localeCode): void;

    /**
     * @return mixed
     */
    public function getValue();

    /**
     * @param mixed $value
     */
    public function setValue($value): void;

    public function getCode(): ?string;

    public function getName(): ?string;

    public function getType(): ?string;

    public function getTaxon(): ?TaxonInterface;

    public function setTaxon(?TaxonInterface $taxon): void;
}
