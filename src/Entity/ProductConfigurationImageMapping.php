<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @ORM\Entity()
 *
 * @ORM\Table("akeneo_api_configuration_product_images_mapping")
 */
final class ProductConfigurationImageMapping implements ResourceInterface
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
    private $id;

    /**
     * @SerializedName("sylius_attribute")
     *
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $syliusAttribute;

    /**
     * @SerializedName("akeneo_attribute")
     *
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $akeneoAttribute;

    /**
     * @var ProductConfiguration|null
     *
     * @ORM\ManyToOne(targetEntity="ProductConfiguration", inversedBy="productImagesMapping")
     *
     * @ORM\JoinColumn(nullable=false)
     */
    private $productConfiguration;

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
