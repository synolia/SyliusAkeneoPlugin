<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

trait ProductAssetTrait
{
    /** @ORM\ManyToMany(targetEntity=\Synolia\SyliusAkeneoPlugin\Entity\Asset::class, mappedBy="owner") */
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
            $asset->addOwner($this);
        }

        return $this;
    }

    public function removeAsset(Asset $asset): self
    {
        if ($this->assets->removeElement($asset)) {
            $asset->removeOwner($this);
        }

        return $this;
    }
}
