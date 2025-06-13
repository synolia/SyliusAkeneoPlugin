<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Sylius\Component\Core\Model\ProductInterface;

#[ORM\Entity]
#[ORM\Table(name: 'akeneo_product_group')]
class ProductGroup implements ProductGroupInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ProductGroupInterface::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?ProductGroupInterface $parent = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true, nullable: false)]
    private string $model;

    #[ORM\Column(type: Types::ARRAY)]
    private array $variationAxes = [];

    /** @ORM\Column(type="string") */
    #[ORM\Column(type: Types::STRING)]
    private string $family = '';

    /** @ORM\Column(type="string") */
    #[ORM\Column(type: Types::STRING)]
    private string $familyVariant = '';

    #[JoinTable(name: 'akeneo_productgroup_product')]
    #[JoinColumn(name: 'product_id', referencedColumnName: 'id')]
    #[InverseJoinColumn(name: 'productgroup_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: ProductInterface::class)]
    private Collection $products;

    #[ORM\Column(type: Types::ARRAY)]
    private array $associations = [];

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParent(): ?ProductGroupInterface
    {
        return $this->parent;
    }

    public function setParent(?ProductGroupInterface $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function setModel(string $model): ProductGroupInterface
    {
        $this->model = $model;

        return $this;
    }

    public function getModel(): string
    {
        return $this->model;
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

    public function getFamilyVariant(): string
    {
        return $this->familyVariant;
    }

    public function setFamilyVariant(string $familyVariant): ProductGroupInterface
    {
        $this->familyVariant = $familyVariant;

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
