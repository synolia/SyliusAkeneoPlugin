<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Product;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class ProductItemPayload extends AbstractPayload
{
    private Collection $products;

    public function getProducts(): Collection
    {
        if (!$this->products instanceof Collection) {
            $this->products = new ArrayCollection();
        }

        return $this->products;
    }
}
