<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[ORM\Entity]
#[ORM\Table(name: 'akeneo_api_configuration_product_images_mapping')]
class ProductConfigurationImageMapping implements ResourceInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    #[SerializedName('sylius_attribute')]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $syliusAttribute = null;

    #[SerializedName('akeneo_attribute')]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $akeneoAttribute = null;

    #[ORM\ManyToOne(targetEntity: ProductConfiguration::class, inversedBy: 'productImagesMapping')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProductConfiguration $productConfiguration = null;

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
