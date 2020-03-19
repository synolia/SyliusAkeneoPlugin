<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @ORM\Entity()
 * @ORM\Table("akeneo_api_configuration_product_default_tax")
 */
class ProductConfigurationDefaultTax implements ResourceInterface
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
    private $website;

    /**
     * @SerializedName("tax_class")
     *
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $taxClass;

    /**
     * @var ProductConfiguration|null
     * @ORM\ManyToOne(targetEntity="ProductConfiguration", inversedBy="defaultTax")
     * @ORM\JoinColumn(nullable=false)
     */
    private $productConfiguration;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(string $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getTaxClass(): ?string
    {
        return $this->taxClass;
    }

    public function setTaxClass(string $taxClass): self
    {
        $this->taxClass = $taxClass;

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
