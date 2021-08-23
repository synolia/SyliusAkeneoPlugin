<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Doctrine\Common\Collections\Collection;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;

class ExcludedAttributesProvider implements ExcludedAttributesProviderInterface
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\ProductConfigurationProviderInterface */
    private $productConfigurationProvider;

    public function __construct(ProductConfigurationProviderInterface $productConfigurationProvider)
    {
        $this->productConfigurationProvider = $productConfigurationProvider;
    }

    public function getExcludedAttributes(): array
    {
        $excludedAttributeCodes = [];
        /** @var \Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration|null $productConfiguration */
        $productConfiguration = $this->productConfigurationProvider->getProductConfiguration();

        if (!$productConfiguration instanceof ProductConfiguration) {
            return [];
        }

        if (null !== $productConfiguration->getAkeneoPriceAttribute()) {
            $excludedAttributeCodes[] = $productConfiguration->getAkeneoPriceAttribute();
        }

        if (null !== $productConfiguration->getAkeneoEnabledChannelsAttribute()) {
            $excludedAttributeCodes[] = $productConfiguration->getAkeneoEnabledChannelsAttribute();
        }

        if ($productConfiguration->getAkeneoImageAttributes() instanceof Collection &&
            $productConfiguration->getAkeneoImageAttributes()->count() > 0) {
            foreach ($productConfiguration->getAkeneoImageAttributes() as $akeneoImageAttribute) {
                $excludedAttributeCodes[] = $akeneoImageAttribute->getAkeneoAttributes();
            }
        }

        return $excludedAttributeCodes;
    }
}
