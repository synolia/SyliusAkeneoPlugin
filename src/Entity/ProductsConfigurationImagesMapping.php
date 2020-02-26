<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * @ORM\Entity()
 * @ORM\Table("akeneo_api_configuration_products_images_mapping")
 */
class ProductsConfigurationImagesMapping implements ResourceInterface
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
    private $syliusAttribute;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $akeneoAttribute;

    /**
     * @var ProductsConfiguration|null
     * @ORM\ManyToOne(targetEntity="Synolia\SyliusAkeneoPlugin\Entity\ProductsConfiguration", inversedBy="defaultTax")
     * @ORM\JoinColumn(nullable=false)
     */
    private $productsConfiguration;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSyliusAttribute(): ?string
    {
        return $this->syliusAttribute;
    }

    public function setSyliusAttribute(string $syliusAttribute): self
    {
        $this->syliusAttribute = $syliusAttribute;

        return $this;
    }

    public function getAkeneoAttribute(): ?string
    {
        return $this->akeneoAttribute;
    }

    public function setAkeneoAttribute(string $akeneoAttribute): self
    {
        $this->akeneoAttribute = $akeneoAttribute;

        return $this;
    }

    public function getProductsConfiguration(): ?ProductsConfiguration
    {
        return $this->productsConfiguration;
    }

    public function setProductsConfiguration(?ProductsConfiguration $productsConfiguration): self
    {
        $this->productsConfiguration = $productsConfiguration;

        return $this;
    }
}
