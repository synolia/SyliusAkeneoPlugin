<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Sylius\Component\Attribute\Model\AttributeInterface;

interface TaxonAttributeInterface extends AttributeInterface
{
    public function getNameByLocaleCode(string $localeCode): string;

    public function getCode(): ?string;

    public function setCode(?string $code): void;
}
