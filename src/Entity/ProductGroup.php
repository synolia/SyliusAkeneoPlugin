<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinTable;
use Sylius\Component\Core\Model\ProductInterface;

/**
 * @ORM\Entity()
 * @ORM\Table("akeneo_product_group")
 */
class ProductGroup implements ProductGroupInterface
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
     * @var array
     * @ORM\Column(type="array")
     */
    private $variationAxes = [];

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $family = '';

    /**
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="Sylius\Component\Core\Model\Product")
     * @JoinTable(name="akeneo_productgroup_product")
     */
    private $products;

    /** @ORM\Column(type="array") */
    private array $associations = [];

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setProductParent(string $productParent): ProductGroupInterface
    {
        $this->productParent = $productParent;

        return $this;
    }

    public function getProductParent(): string
    {
        return $this->productParent;
    }

    /**
     * @return array|string[]
     */
    public function getVariationAxes(): array
    {
        return $this->variationAxes;
    }

    public function setVariationAxes(array $variationAxes): ProductGroupInterface
    {
        $this->variationAxes = $variationAxes;

        return $this;
    }

    public function addVariationAxe(string $variationAxe): ProductGroupInterface
    {
        if (\in_array($variationAxe, $this->variationAxes)) {
            return $this;
        }

        $this->variationAxes[] = $variationAxe;

        return $this;
    }

    public function removeVariationAxe(string $variationAxe): ProductGroupInterface
    {
        if (!\in_array($variationAxe, $this->variationAxes)) {
            return $this;
        }

        unset($this->variationAxes[array_search($variationAxe, $this->variationAxes)]);

        return $this;
    }

    /**
     * @return array|string[]
     */
    public function getAssociations(): array
    {
        return $this->associations;
    }

    public function setAssociations(array $associations): ProductGroupInterface
    {
        $this->associations = $associations;

        return $this;
    }

    public function addAssociation(string $association): ProductGroupInterface
    {
        if (\in_array($association, $this->associations)) {
            return $this;
        }

        $this->associations[] = $association;

        return $this;
    }

    public function removeAssociation(string $association): ProductGroupInterface
    {
        if (!\in_array($association, $this->associations)) {
            return $this;
        }

        unset($this->associations[array_search($association, $this->associations)]);

        return $this;
    }

    public function getFamily(): string
    {
        return $this->family;
    }

    public function setFamily(string $family): ProductGroupInterface
    {
        $this->family = $family;

        return $this;
    }

    /**
     * @return Collection|ProductInterface[]
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(ProductInterface $product): ProductGroupInterface
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
        }

        return $this;
    }

    public function removeProduct(ProductInterface $product): ProductGroupInterface
    {
        if ($this->products->contains($product)) {
            $this->products->removeElement($product);
        }

        return $this;
    }
}
