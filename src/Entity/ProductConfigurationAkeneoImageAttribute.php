<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @ORM\Entity()
 * @ORM\Table("akeneo_api_configuration_product_akeneo_image_attribute")
 */
class ProductConfigurationAkeneoImageAttribute implements ResourceInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @SerializedName("akeneo_attributes")
     *
     * @ORM\Column(type="string", length=255)
     */
    private ?string $akeneoAttributes = null;

    /**
     * @ORM\ManyToOne(targetEntity="ProductConfiguration", inversedBy="akeneoImageAttributes")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?ProductConfiguration $productConfiguration = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAkeneoAttributes(): ?string
    {
        return $this->akeneoAttributes;
    }

    public function setAkeneoAttributes(string $akeneoAttributes): self
    {
        $this->akeneoAttributes = $akeneoAttributes;

        return $this;
    }

    public function getProductConfiguration(): ?ProductConfiguration
    {
        return $this->productConfiguration;
    }

    public function setProductConfiguration(?ProductConfiguration $productConfiguration): self
    {
        $this->productConfiguration = $productConfiguration;

        return $this;
    }
}
