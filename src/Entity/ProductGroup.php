<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinTable;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * @ORM\Entity()
 * @ORM\Table("akeneo_product_group")
 */
class ProductGroup implements ResourceInterface
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
     * @ORM\Column(type="string", length=255, nullable=false, unique=true)
     */
    private $productParent;

    /**
     * @var ArrayCollection
     * @ORM\Column(type="array")
     */
    private $variationAxes;

    /**
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="Sylius\Component\Core\Model\Product")
     * @JoinTable(name="akeneo_productgroup_product")
     */
    private $products;

    public function __construct()
    {
        $this->variationAxes = new ArrayCollection();
        $this->products = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setProductParent(string $productParent): self
    {
        $this->productParent = $productParent;

        return $this;
    }

    public function getProductParent(): string
    {
        return $this->productParent;
    }

    /**
     * @return ArrayCollection|string[]
     */
    public function getVariationAxes(): ArrayCollection
    {
        return $this->variationAxes;
    }

    public function addVariationAxe(string $variationAxe): self
    {
        if (!$this->variationAxes->contains($variationAxe)) {
            $this->variationAxes->add($variationAxe);
        }

        return $this;
    }

    public function removeVariationAxe(string $variationAxe): self
    {
        if ($this->variationAxes->contains($variationAxe)) {
            $this->variationAxes->removeElement($variationAxe);
        }

        return $this;
    }

    /**
     * @return Collection|ProductInterface[]
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(ProductInterface $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
        }

        return $this;
    }

    public function removeProduct(ProductInterface $product): self
    {
        if ($this->products->contains($product)) {
            $this->products->removeElement($product);
        }

        return $this;
    }
}
