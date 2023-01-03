<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * @ORM\Entity(repositoryClass="Synolia\SyliusAkeneoPlugin\Repository\ProductConfigurationRepository")
 * @ORM\Table("akeneo_api_configuration_product")
 */
class ProductConfiguration implements ResourceInterface
{
    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /** @ORM\Column(type="string", length=255, nullable=true) */
    private ?string $akeneoPriceAttribute = null;

    /** @ORM\Column(type="string", length=255, nullable=true) */
    private ?string $akeneoEnabledChannelsAttribute = null;

    /** @ORM\Column(type="array", nullable=true) */
    private ?array $attributeMapping = null;

    /** @ORM\Column(type="boolean", nullable=true) */
    private ?bool $importMediaFiles = null;

    /**
     * @ORM\OneToMany(
     *     targetEntity="ProductConfigurationAkeneoImageAttribute",
     *     mappedBy="productConfiguration",
     *     orphanRemoval=true,
     *     cascade={"persist"}
     * )
     */
    private Collection $akeneoImageAttributes;

    /**
     * @ORM\OneToMany(
     *     targetEntity="ProductConfigurationImageMapping",
     *     mappedBy="productConfiguration",
     *     orphanRemoval=true,
     *     cascade={"persist"}
     * )
     */
    private Collection $productImagesMapping;

    /** @ORM\Column(type="boolean", nullable=true) */
    private ?bool $regenerateUrlRewrites = null;

    public function __construct()
    {
        $this->akeneoImageAttributes = new ArrayCollection();
        $this->productImagesMapping = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAttributeMapping(): ?array
    {
        return $this->attributeMapping;
    }

    public function setAttributeMapping(?array $attributeMapping): self
    {
        $this->attributeMapping = $attributeMapping;

        return $this;
    }

    public function getImportMediaFiles(): ?bool
    {
        return $this->importMediaFiles;
    }

    public function setImportMediaFiles(?bool $importMediaFiles): self
    {
        $this->importMediaFiles = $importMediaFiles;

        return $this;
    }

    /**
     * @return Collection|ProductConfigurationAkeneoImageAttribute[]
     */
    public function getAkeneoImageAttributes(): ?Collection
    {
        return $this->akeneoImageAttributes;
    }

    public function addAkeneoImageAttribute(ProductConfigurationAkeneoImageAttribute $akeneoImageAttributes): self
    {
        if (!$this->akeneoImageAttributes->contains($akeneoImageAttributes)) {
            $this->akeneoImageAttributes[] = $akeneoImageAttributes;
            $akeneoImageAttributes->setProductConfiguration($this);
        }

        return $this;
    }

    public function removeAkeneoImageAttribute(ProductConfigurationAkeneoImageAttribute $akeneoImageAttributes): self
    {
        if ($this->akeneoImageAttributes->contains($akeneoImageAttributes)) {
            $this->akeneoImageAttributes->removeElement($akeneoImageAttributes);
            if ($akeneoImageAttributes->getProductConfiguration() === $this) {
                $akeneoImageAttributes->setProductConfiguration(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ProductConfigurationImageMapping[]
     */
    public function getProductImagesMapping(): ?Collection
    {
        return $this->productImagesMapping;
    }

    public function addProductImagesMapping(ProductConfigurationImageMapping $productImagesMapping): self
    {
        if (!$this->productImagesMapping->contains($productImagesMapping)) {
            $this->productImagesMapping[] = $productImagesMapping;
            $productImagesMapping->setProductConfiguration($this);
        }

        return $this;
    }

    public function removeProductImagesMapping(ProductConfigurationImageMapping $productImagesMapping): self
    {
        if ($this->productImagesMapping->contains($productImagesMapping)) {
            $this->productImagesMapping->removeElement($productImagesMapping);
            if ($productImagesMapping->getProductConfiguration() === $this) {
                $productImagesMapping->setProductConfiguration(null);
            }
        }

        return $this;
    }

    public function getRegenerateUrlRewrites(): ?bool
    {
        return $this->regenerateUrlRewrites;
    }

    public function setRegenerateUrlRewrites(?bool $regenerateUrlRewrites): self
    {
        $this->regenerateUrlRewrites = $regenerateUrlRewrites;

        return $this;
    }

    public function getAkeneoPriceAttribute(): ?string
    {
        return $this->akeneoPriceAttribute;
    }

    public function setAkeneoPriceAttribute(?string $akeneoPriceAttribute): self
    {
        $this->akeneoPriceAttribute = $akeneoPriceAttribute;

        return $this;
    }

    public function getAkeneoEnabledChannelsAttribute(): ?string
    {
        return $this->akeneoEnabledChannelsAttribute;
    }

    public function setAkeneoEnabledChannelsAttribute(?string $akeneoEnabledChannelsAttribute): self
    {
        $this->akeneoEnabledChannelsAttribute = $akeneoEnabledChannelsAttribute;

        return $this;
    }
}
