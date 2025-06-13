<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

trait ProductVariantAssetTrait
{
    #[ORM\ManyToMany(targetEntity: Asset::class, mappedBy: 'productVariants')]
    private $assets;

    public function __construct()
    {
        $this->assets = new ArrayCollection();
    }

    /**
     * @return Collection|Asset[]
     */
    public function getAssets(): Collection
    {
        return $this->assets;
    }

    public function addAsset(Asset $asset): self
    {
        if (!$this->assets->contains($asset)) {
            $this->assets[] = $asset;
            $asset->addProductVariant($this);
        }

        return $this;
    }

    public function removeAsset(Asset $asset): self
    {
        if ($this->assets->removeElement($asset)) {
            $asset->removeProductVariant($this);
        }

        return $this;
    }
}
