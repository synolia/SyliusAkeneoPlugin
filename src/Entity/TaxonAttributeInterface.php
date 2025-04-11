<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Sylius\Component\Attribute\Model\AttributeTranslationInterface;
use Sylius\Component\Resource\Model\CodeAwareInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;
use Sylius\Component\Resource\Model\TranslatableInterface;
use Sylius\Component\Resource\Model\TranslationInterface;

interface TaxonAttributeInterface extends
    ResourceInterface,
    CodeAwareInterface,
    TimestampableInterface,
    TranslatableInterface
{
    public function getName(): ?string;

    public function setName(?string $name): void;

    public function getType(): ?string;

    public function setType(?string $type): void;

    public function getConfiguration(): array;

    public function setConfiguration(array $configuration): void;

    public function getStorageType(): ?string;

    public function setStorageType(string $storageType): void;

    public function getPosition(): ?int;

    public function setPosition(?int $position): void;

    public function isTranslatable(): bool;

    public function setTranslatable(bool $translatable): void;

    /**
     * @return AttributeTranslationInterface
     */
    public function getTranslation(?string $locale = null): TranslationInterface;

    public function getNameByLocaleCode(string $localeCode): string;
}
