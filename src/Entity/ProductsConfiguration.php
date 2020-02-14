<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * @ORM\Entity(repositoryClass="Synolia\SyliusAkeneoPlugin\Repository\ProductsConfigurationRepository")
 * @ORM\Table("akeneo_api_configuration_products_configuration")
 */
class ProductsConfiguration implements ResourceInterface
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
     *     targetEntity="Synolia\SyliusAkeneoPlugin\Entity\ProductsConfigurationDefaultTax",
     *     mappedBy="productsConfiguration",
     *     orphanRemoval=true,
     *     cascade={"persist"}
     * )
     */
    private $defaultTax;

    /**
     * @var Collection
     * @ORM\OneToMany(
     *     targetEntity="Synolia\SyliusAkeneoPlugin\Entity\ProductsConfigurationAttributes",
     *     mappedBy="productsConfiguration",
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
     * @var array|null
     * @ORM\Column(type="array", nullable=true)
     */
    private $akeneoImageAttributes;

    /**
     * @var array|null
     * @ORM\Column(type="array", nullable=true)
     */
    private $productImagesMapping;

    /**
     * @var bool|null
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $importAssetFiles;

    /**
     * @var array|null
     * @ORM\Column(type="array", nullable=true)
     */
    private $akeneoAssetAttributes;

    /**
     * @var bool|null
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $regenerateUrlRewrites;

    public function __construct()
    {
        $this->defaultTax = new ArrayCollection();
        $this->configurable = new ArrayCollection();
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
     * @return Collection|ProductsConfigurationDefaultTax[]
     */
    public function getDefaultTax(): Collection
    {
        return $this->defaultTax;
    }

    public function addDefaultTax(ProductsConfigurationDefaultTax $defaultTax): self
    {
        if (!$this->defaultTax->contains($defaultTax)) {
            $this->defaultTax[] = $defaultTax;
            $defaultTax->setProductsConfiguration($this);
        }

        return $this;
    }

    public function removeDefaultTax(ProductsConfigurationDefaultTax $defaultTax): self
    {
        if ($this->defaultTax->contains($defaultTax)) {
            $this->defaultTax->removeElement($defaultTax);
            // set the owning side to null (unless already changed)
            if ($defaultTax->getProductsConfiguration() === $this) {
                $defaultTax->setProductsConfiguration(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ProductsConfigurationAttributes[]
     */
    public function getConfigurable(): Collection
    {
        return $this->configurable;
    }

    public function addConfigurable(ProductsConfigurationAttributes $configurable): self
    {
        if (!$this->configurable->contains($configurable)) {
            $this->configurable[] = $configurable;
            $configurable->setProductsConfiguration($this);
        }

        return $this;
    }

    public function removeConfigurable(ProductsConfigurationAttributes $configurable): self
    {
        if ($this->configurable->contains($configurable)) {
            $this->configurable->removeElement($configurable);
            // set the owning side to null (unless already changed)
            if ($configurable->getProductsConfiguration() === $this) {
                $configurable->setProductsConfiguration(null);
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

    public function getAkeneoImageAttributes(): ?array
    {
        return $this->akeneoImageAttributes;
    }

    public function setAkeneoImageAttributes(?array $akeneoImageAttributes): self
    {
        $this->akeneoImageAttributes = $akeneoImageAttributes;

        return $this;
    }

    public function getProductImagesMapping(): ?array
    {
        return $this->productImagesMapping;
    }

    public function setProductImagesMapping(?array $productImagesMapping): self
    {
        $this->productImagesMapping = $productImagesMapping;

        return $this;
    }

    public function getImportAssetFiles(): ?bool
    {
        return $this->importAssetFiles;
    }

    public function setImportAssetFiles(?bool $importAssetFiles): self
    {
        $this->importAssetFiles = $importAssetFiles;

        return $this;
    }

    public function getAkeneoAssetAttributes(): ?array
    {
        return $this->akeneoAssetAttributes;
    }

    public function setAkeneoAssetAttributes(?array $akeneoAssetAttributes): self
    {
        $this->akeneoAssetAttributes = $akeneoAssetAttributes;

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
