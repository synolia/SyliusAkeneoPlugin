<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\ExcludedAttributesConfigurationInterface;

final class ExcludedAttributesProvider implements ExcludedAttributesProviderInterface
{
    public function __construct(
        private RepositoryInterface $productConfigurationRepository,
        private ExcludedAttributesConfigurationInterface $excludedAttributesConfiguration,
    ) {
    }

    public function getExcludedAttributes(): array
    {
        $excludedAttributeCodes = $this->excludedAttributesConfiguration->get();

        /** @var \Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration|null $productConfiguration */
        $productConfiguration = $this->productConfigurationRepository->findOneBy([], ['id' => 'DESC']);

        if (!$productConfiguration instanceof ProductConfiguration) {
            return $excludedAttributeCodes;
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
