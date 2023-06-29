<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Sylius\Component\Core\Model\TaxonInterface;

interface TaxonAttributeValueInterface
{
    public function getSubject(): ?TaxonInterface;

    public function setSubject(?TaxonInterface $subject): self;

    public function getAttribute(): ?TaxonAttributeInterface;

    public function setAttribute(?TaxonAttributeInterface $attribute): self;

    public function getLocaleCode(): ?string;

    public function setLocaleCode(?string $localeCode): self;

    /**
     * @return mixed
     */
    public function getValue();

    /**
     * @param mixed $value
     */
    public function setValue($value): self;

    public function getCode(): ?string;

    public function getName(): ?string;

    public function getType(): ?string;

    public function getTaxon(): ?TaxonInterface;

    public function setTaxon(?TaxonInterface $taxon): self;
}
