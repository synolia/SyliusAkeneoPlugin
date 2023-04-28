<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Twig;

use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Component\TaxonAttribute\Model\TaxonAttributeSubjectInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class IsAssetEnabled extends AbstractExtension
{
    private ?bool $enabled = null;

    public function __construct(private ProductFactoryInterface $productFactory)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('akeneo_is_asset_enabled', [$this, 'isAssetEnabled']),
        ];
    }

    public function isAssetEnabled(): bool
    {
        return true;
        if (null !== $this->enabled) {
            return $this->enabled;
        }

        $taxon = $this->productFactory->createNew();

        if (!$taxon instanceof TaxonAttributeSubjectInterface) {
            return $this->enabled = false;
        }

        return $this->enabled = true;
    }
}
