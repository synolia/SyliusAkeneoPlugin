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
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $websiteAttribute;

    /**
     * @var array|null
     * @ORM\Column(type="array", nullable=true)
     */
    private $attributeMapping;

    /**
     * @var Collection
     * @ORM\OneToMany(
     *     targetEntity="ProductConfigurationDefaultTax",
     *     mappedBy="productConfiguration",
     *     orphanRemoval=true,
     *     cascade={"persist"}
     * )
     */
    private $defaultTax;

    /**
     * @var Collection
     * @ORM\OneToMany(
     *     targetEntity="ProductConfigurationAttribute",
     *     mappedBy="productConfiguration",
     *     orphanRemoval=true,
     *     cascade={"persist"}
     * )
     */
    private $configurable;

    /**
     * @var bool|null
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $importMediaFiles;

    /**
     * @var Collection
     * @ORM\OneToMany(
     *     targetEntity="ProductConfigurationAkeneoImageAttribute",
     *     mappedBy="productConfiguration",
     *     orphanRemoval=true,
     *     cascade={"persist"}
     * )
     */
    private $akeneoImageAttributes;

    /**
     * @var Collection
     * @ORM\OneToMany(
     *     targetEntity="ProductConfigurationImageMapping",
     *     mappedBy="productConfiguration",
     *     orphanRemoval=true,
     *     cascade={"persist"}
     * )
     */
    private $productImagesMapping;

    /**
     * @var bool|null
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $regenerateUrlRewrites;

    public function __construct()
    {
        $this->defaultTax = new ArrayCollection();
        $this->configurable = new ArrayCollection();
        $this->akeneoImageAttributes = new ArrayCollection();
        $this->productImagesMapping = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWebsiteAttribute(): ?string
    {
        return $this->websiteAttribute;
    }

    public function setWebsiteAttribute(?string $websiteAttribute): self
    {
        $this->websiteAttribute = $websiteAttribute;

        return $this;
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

    /**
     * @return Collection|ProductConfigurationDefaultTax[]
     */
    public function getDefaultTax(): Collection
    {
        return $this->defaultTax;
    }

    public function addDefaultTax(ProductConfigurationDefaultTax $defaultTax): self
    {
        if (!$this->defaultTax->contains($defaultTax)) {
            $this->defaultTax[] = $defaultTax;
            $defaultTax->setProductConfiguration($this);
        }

        return $this;
    }

    public function removeDefaultTax(ProductConfigurationDefaultTax $defaultTax): self
    {
        if ($this->defaultTax->contains($defaultTax)) {
            $this->defaultTax->removeElement($defaultTax);
            if ($defaultTax->getProductConfiguration() === $this) {
                $defaultTax->setProductConfiguration(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ProductConfigurationAttribute[]
     */
    public function getConfigurable(): Collection
    {
        return $this->configurable;
    }

    public function addConfigurable(ProductConfigurationAttribute $configurable): self
    {
        if (!$this->configurable->contains($configurable)) {
            $this->configurable[] = $configurable;
            $configurable->setProductConfiguration($this);
        }

        return $this;
    }

    public function removeConfigurable(ProductConfigurationAttribute $configurable): self
    {
        if ($this->configurable->contains($configurable)) {
            $this->configurable->removeElement($configurable);
            if ($configurable->getProductConfiguration() === $this) {
                $configurable->setProductConfiguration(null);
            }
        }

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
}
