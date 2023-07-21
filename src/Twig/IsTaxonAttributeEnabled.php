<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Twig;

use Sylius\Component\Taxonomy\Factory\TaxonFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Component\TaxonAttribute\Model\TaxonAttributeSubjectInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class IsTaxonAttributeEnabled extends AbstractExtension
{
    private ?bool $enabled = null;

    public function __construct(private TaxonFactoryInterface $taxonFactory)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('akeneo_is_taxon_attribute_enabled', [$this, 'isTaxonAttributeEnabled']),
        ];
    }

    public function isTaxonAttributeEnabled(): bool
    {
        if (null !== $this->enabled) {
            return $this->enabled;
        }

        $taxon = $this->taxonFactory->createNew();

        if (!$taxon instanceof TaxonAttributeSubjectInterface) {
            return $this->enabled = false;
        }

        return $this->enabled = true;
    }
}
