<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * @ORM\Entity()
 * @ORM\Table("akeneo_api_configuration_products_configuration_attributes")
 */
class ProductsConfigurationAttributes implements ResourceInterface
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
    private $attribute;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $value;

    /**
     * @var ProductsConfiguration|null
     * @ORM\ManyToOne(targetEntity="Synolia\SyliusAkeneoPlugin\Entity\ProductsConfiguration", inversedBy="configurable")
     * @ORM\JoinColumn(nullable=false)
     */
    private $productsConfiguration;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAttribute(): ?string
    {
        return $this->attribute;
    }

    public function setAttribute(string $attribute): self
    {
        $this->attribute = $attribute;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

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
