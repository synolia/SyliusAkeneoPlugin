<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * @ORM\Entity(repositoryClass="Synolia\SyliusAkeneoPlugin\Repository\ProductFiltersRulesRepository")
 * @ORM\Table("akeneo_api_product_filters_rules")
 */
final class ProductFiltersRules implements ResourceInterface
{
    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $mode;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $advancedFilter;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $completenessType;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $locales;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $completenessValue;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $status;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $updatedMode;

    /**
     * @var \DateTimeInterface|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedBefore;

    /**
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAfter;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $updated;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $families;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMode(): ?string
    {
        return $this->mode;
    }

    public function setMode(string $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    public function getAdvancedFilter(): ?string
    {
        return $this->advancedFilter;
    }

    public function setAdvancedFilter(?string $advancedFilter): self
    {
        $this->advancedFilter = $advancedFilter;

        return $this;
    }

    public function getCompletenessType(): ?string
    {
        return $this->completenessType;
    }

    public function setCompletenessType(?string $completenessType): self
    {
        $this->completenessType = $completenessType;

        return $this;
    }

    public function getLocales(): ?string
    {
        return $this->locales;
    }

    public function setLocales(string $locales): self
    {
        $this->locales = $locales;

        return $this;
    }

    public function getCompletenessValue(): ?string
    {
        return $this->completenessValue;
    }

    public function setCompletenessValue(?string $completenessValue): self
    {
        $this->completenessValue = $completenessValue;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getUpdatedMode(): ?string
    {
        return $this->updatedMode;
    }

    public function setUpdatedMode(?string $updatedMode): self
    {
        $this->updatedMode = $updatedMode;

        return $this;
    }

    public function getUpdatedBefore(): ?\DateTimeInterface
    {
        return $this->updatedBefore;
    }

    public function setUpdatedBefore(?\DateTimeInterface $updatedBefore): self
    {
        $this->updatedBefore = $updatedBefore;

        return $this;
    }

    public function getUpdatedAfter(): ?\DateTimeInterface
    {
        return $this->updatedAfter;
    }

    public function setUpdatedAfter(?\DateTimeInterface $updatedAfter): self
    {
        $this->updatedAfter = $updatedAfter;

        return $this;
    }

    public function getUpdated(): ?string
    {
        return $this->updated;
    }

    public function setUpdated(?string $updated): self
    {
        $this->updated = $updated;

        return $this;
    }

    public function getFamilies(): ?string
    {
        return $this->families;
    }

    public function setFamilies(?string $families): self
    {
        $this->families = $families;

        return $this;
    }
}
