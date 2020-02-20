<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * @ORM\Entity(repositoryClass="Synolia\SyliusAkeneoPlugin\Repository\ProductsConfigurationRepository")
 * @ORM\Table("akeneo_api_configuration_products")
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
     * @var Collection
     * @ORM\OneToMany(
     *     targetEntity="Synolia\SyliusAkeneoPlugin\Entity\ProductsConfigurationAkeneoImageAttributes",
     *     mappedBy="productsConfiguration",
     *     orphanRemoval=true,
     *     cascade={"persist"}
     * )
     */
    private $akeneoImageAttributes;

    /**
     * @var Collection
     * @ORM\OneToMany(
     *     targetEntity="Synolia\SyliusAkeneoPlugin\Entity\ProductsConfigurationImagesMapping",
     *     mappedBy="productsConfiguration",
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

    /**
     * @return Collection|ProductsConfigurationAkeneoImageAttributes[]
     */
    public function getAkeneoImageAttributes(): ?Collection
    {
        return $this->akeneoImageAttributes;
    }

    public function addAkeneoImageAttribute(ProductsConfigurationAkeneoImageAttributes $akeneoImageAttributes): self
    {
        if (!$this->akeneoImageAttributes->contains($akeneoImageAttributes)) {
            $this->akeneoImageAttributes[] = $akeneoImageAttributes;
            $akeneoImageAttributes->setProductsConfiguration($this);
        }

        return $this;
    }

    public function removeAkeneoImageAttribute(ProductsConfigurationAkeneoImageAttributes $akeneoImageAttributes): self
    {
        if ($this->akeneoImageAttributes->contains($akeneoImageAttributes)) {
            $this->akeneoImageAttributes->removeElement($akeneoImageAttributes);
            if ($akeneoImageAttributes->getProductsConfiguration() === $this) {
                $akeneoImageAttributes->setProductsConfiguration(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ProductsConfigurationImagesMapping[]
     */
    public function getProductImagesMapping(): ?Collection
    {
        return $this->productImagesMapping;
    }

    public function addProductImagesMapping(ProductsConfigurationImagesMapping $productImagesMapping): self
    {
        if (!$this->productImagesMapping->contains($productImagesMapping)) {
            $this->productImagesMapping[] = $productImagesMapping;
            $productImagesMapping->setProductsConfiguration($this);
        }

        return $this;
    }

    public function removeProductImagesMapping(ProductsConfigurationImagesMapping $productImagesMapping): self
    {
        if ($this->productImagesMapping->contains($productImagesMapping)) {
            $this->productImagesMapping->removeElement($productImagesMapping);
            if ($productImagesMapping->getProductsConfiguration() === $this) {
                $productImagesMapping->setProductsConfiguration(null);
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
