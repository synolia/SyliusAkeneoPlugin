<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @ORM\Entity()
 *
 * @ORM\Table("akeneo_api_configuration_product_images_mapping")
 */
#[ORM\Entity]
#[ORM\Table(name: 'akeneo_api_configuration_product_images_mapping')]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class ProductConfigurationImageMapping implements ResourceInterface
{
    /**
     * @var int
     *
     * @ORM\Id()
     *
     * @ORM\GeneratedValue()
     *
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @SerializedName("sylius_attribute")
     *
     * @ORM\Column(type="string", length=255)
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $syliusAttribute = null;

    /**
     * @SerializedName("akeneo_attribute")
     *
     * @ORM\Column(type="string", length=255)
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $akeneoAttribute = null;

    /**
     * @ORM\ManyToOne(targetEntity="ProductConfiguration", inversedBy="productImagesMapping")
     *
     * @ORM\JoinColumn(nullable=false)
     */
    #[ORM\ManyToOne(targetEntity: ProductConfiguration::class, inversedBy: 'productImagesMapping')]
    #[ORM\JoinColumn(nullable: false)]
    private ?\Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration $productConfiguration = null;

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
