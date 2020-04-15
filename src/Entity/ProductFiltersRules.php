<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * @ORM\Entity(repositoryClass="Synolia\SyliusAkeneoPlugin\Repository\ProductFiltersRulesRepository")
 * @ORM\Table("akeneo_api_product_filters_rules")
 */
class ProductFiltersRules implements ResourceInterface
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
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $completenessType = '';

    /**
     * @var array
     * @ORM\Column(type="array")
     */
    private $locales = [];

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $completenessValue = '';

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
     * @var \DateTimeInterface
     * @ORM\Column(type="datetime")
     */
    private $updatedBefore;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(type="datetime")
     */
    private $updatedAfter;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $updated;

    /**
     * @var array
     * @ORM\Column(type="array")
     */
    private $families = [];

    public function __construct()
    {
        $this->updatedBefore = new \DateTime();
        $this->updatedAfter = new \DateTime();
    }

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

    public function getCompletenessType(): string
    {
        return $this->completenessType;
    }

    public function setCompletenessType(string $completenessType): self
    {
        $this->completenessType = $completenessType;

        return $this;
    }

    public function getLocales(): array
    {
        return $this->locales;
    }

    public function addLocale(string $locale): self
    {
        if (in_array($locale, $this->locales)) {
            return $this;
        }

        $this->locales[] = $locale;

        $this->locales = array_values($this->locales);

        return $this;
    }

    public function removeLocale(string $locale): self
    {
        if (!in_array($locale, $this->locales)) {
            return $this;
        }

        unset($this->locales[array_search($locale, $this->locales)]);

        $this->locales = array_values($this->locales);

        return $this;
    }

    public function getCompletenessValue(): string
    {
        return $this->completenessValue;
    }

    public function setCompletenessValue(string $completenessValue): self
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

    public function getUpdatedBefore(): \DateTimeInterface
    {
        return $this->updatedBefore;
    }

    public function setUpdatedBefore(\DateTimeInterface $updatedBefore): self
    {
        $this->updatedBefore = $updatedBefore;

        return $this;
    }

    public function getUpdatedAfter(): \DateTimeInterface
    {
        return $this->updatedAfter;
    }

    public function setUpdatedAfter(\DateTimeInterface $updatedAfter): self
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

    public function getFamilies(): array
    {
        return $this->families;
    }

    public function addFamily(string $family): self
    {
        if (in_array($family, $this->families)) {
            return $this;
        }

        $this->families[] = $family;

        $this->families = array_values($this->families);

        return $this;
    }

    public function removeFamily(string $family): self
    {
        if (!in_array($family, $this->families)) {
            return $this;
        }

        unset($this->families[array_search($family, $this->families)]);

        $this->families = array_values($this->families);

        return $this;
    }
}
