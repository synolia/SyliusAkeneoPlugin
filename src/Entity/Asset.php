<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/**
 * @ApiResource()
 *
 * @ORM\Entity(repositoryClass=\Synolia\SyliusAkeneoPlugin\Repository\AssetRepository::class)
 *
 * @ORM\Table(name="akeneo_assets")
 */
class Asset implements AssetInterface
{
    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /** @ORM\Column(name="family_code", type="string", length=255) */
    private string $familyCode;

    /** @ORM\Column(name="asset_code", type="string", length=255) */
    private string $assetCode;

    /** @ORM\Column(name="attribute_code", type="string", length=255) */
    private string $attributeCode;

    /** @ORM\Column(type="string", length=255) */
    private string $type;

    /** @ORM\Column(type="string", length=255) */
    private string $locale;

    /** @ORM\Column(type="string", length=255) */
    private string $scope;

    /**
     * @var Collection|ProductInterface[]
     *
     * @ORM\ManyToMany(targetEntity=ProductInterface::class, inversedBy="assets")
     *
     * @ORM\JoinTable(name="akeneo_assets_products",
     *    joinColumns={@ORM\JoinColumn(name="asset_id", referencedColumnName="id")},
     *    inverseJoinColumns={@ORM\JoinColumn(name="owner_id", referencedColumnName="id", onDelete="CASCADE")},
     * )
     */
    private Collection $owner;

    /**
     * @var Collection|ProductVariantInterface[]
     *
     * @ORM\ManyToMany(targetEntity=ProductVariantInterface::class, inversedBy="assets")
     *
     * @ORM\JoinTable(name="akeneo_assets_product_variants",
     *    joinColumns={@ORM\JoinColumn(name="asset_id", referencedColumnName="id")},
     *    inverseJoinColumns={@ORM\JoinColumn(name="variant_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    private Collection $productVariants;

    /** @ORM\Column(type="json") */
    private array $content = [];

    public function __construct()
    {
        $this->owner = new ArrayCollection();
        $this->productVariants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection|ProductInterface[]
     */
    public function getOwner(): Collection
    {
        return $this->owner;
    }

    public function addOwner(ProductInterface $owner): self
    {
        if (!$this->owner->contains($owner)) {
            $this->owner[] = $owner;
        }

        return $this;
    }

    public function removeOwner(ProductInterface $owner): self
    {
        $this->owner->removeElement($owner);

        return $this;
    }

    /**
     * @return Collection|ProductVariantInterface[]
     */
    public function getProductVariants(): Collection
    {
        return $this->productVariants;
    }

    public function addProductVariant(ProductVariantInterface $productVariant): self
    {
        if (!$this->productVariants->contains($productVariant)) {
            $this->productVariants[] = $productVariant;
        }

        return $this;
    }

    public function removeProductVariant(ProductVariantInterface $productVariant): self
    {
        $this->productVariants->removeElement($productVariant);

        return $this;
    }

    public function getContent(): array
    {
        return $this->content;
    }

    public function setContent(array $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getFamilyCode(): string
    {
        return $this->familyCode;
    }

    public function setFamilyCode(string $familyCode): self
    {
        $this->familyCode = $familyCode;

        return $this;
    }

    public function getAssetCode(): string
    {
        return $this->assetCode;
    }

    public function setAssetCode(string $assetCode): self
    {
        $this->assetCode = $assetCode;

        return $this;
    }

    public function getAttributeCode(): string
    {
        return $this->attributeCode;
    }

    public function setAttributeCode(string $attributeCode): self
    {
        $this->attributeCode = $attributeCode;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function setScope(string $scope): self
    {
        $this->scope = $scope;

        return $this;
    }
}
