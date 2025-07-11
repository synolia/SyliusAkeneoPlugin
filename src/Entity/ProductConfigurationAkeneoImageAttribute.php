<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[ORM\Entity]
#[ORM\Table(name: 'akeneo_api_configuration_product_akeneo_image_attribute')]
class ProductConfigurationAkeneoImageAttribute implements ResourceInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    #[SerializedName('akeneo_attributes')]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $akeneoAttributes = null;

    #[ORM\ManyToOne(targetEntity: ProductConfiguration::class, inversedBy: 'akeneoImageAttributes')]
    #[ORM\JoinColumn(nullable: false)]
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
