<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Asset\Attribute;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\Asset;
use Synolia\SyliusAkeneoPlugin\Provider\Asset\AkeneoAssetAttributeDataProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Asset\AkeneoAssetAttributePropertiesProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Asset\AssetValueBuilderProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\ProductFilterRulesProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;

final class AkeneoAssetAttributeAttributeProcessor implements AkeneoAssetAttributeProcessorInterface
{
    public function __construct(
        private AssetValueBuilderProviderInterface $assetValueBuilderProvider,
        private FactoryInterface $assetFactory,
        private RepositoryInterface $assetRepository,
        private EntityManagerInterface $entityManager,
        private AkeneoAssetAttributePropertiesProviderInterface $akeneoAssetAttributePropertiesProvider,
        private AkeneoAssetAttributeDataProviderInterface $akeneoAssetAttributeDataProvider,
        private SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        private ProductFilterRulesProviderInterface $productFilterRulesProvider,
        private AssetValueBuilderProviderInterface $assetAttributeValueBuilder,
    ) {
    }

    public static function getDefaultPriority(): int
    {
        return -100;
    }

    public function support(
        string $assetFamilyCode,
        string $assetCode,
        string $attributeCode,
        array $assetAttributeResource = [],
    ): bool {
        if (!$this->assetValueBuilderProvider->hasSupportedBuilder($assetFamilyCode, $attributeCode)) {
            return false;
        }

        return true;
    }

    public function process(
        string $assetFamilyCode,
        string $assetCode,
        string $attributeCode,
        array $assetAttributeResource = [],
    ): void {
        if (!$this->support($assetFamilyCode, $assetCode, $attributeCode)) {
            return;
        }

        $scope = $this->productFilterRulesProvider->getProductFiltersRules()->getChannel();
        $isLocalizedAttribute = $this->akeneoAssetAttributePropertiesProvider->isLocalizable($assetFamilyCode, $attributeCode);
        $queryParam = [
            'familyCode' => $assetFamilyCode,
            'attributeCode' => $attributeCode,
            'assetCode' => $assetCode,
            'scope' => $scope,
        ];

        foreach ($assetAttributeResource as $translation) {
            // Skip akeneo locale translation if not active on Sylius
            if ($isLocalizedAttribute &&
                null !== $translation['locale'] &&
                false === $this->syliusAkeneoLocaleCodeProvider->isActiveLocale($translation['locale'])
            ) {
                continue;
            }

            if (!$isLocalizedAttribute) {
                foreach ($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms() as $locale) {
                    $queryParam['locale'] = $locale;
                    $this->handleAsset($assetFamilyCode, $assetCode, $attributeCode, $queryParam, $assetAttributeResource);
                }

                continue;
            }

            $queryParam['locale'] = $translation['locale'];
            $this->handleAsset($assetFamilyCode, $assetCode, $attributeCode, $queryParam, $assetAttributeResource);
        }
    }

    private function handleAsset(
        string $assetFamilyCode,
        string $assetCode,
        string $attributeCode,
        array $queryParam,
        array $assetAttributeResource,
    ): void {
        $asset = $this->assetRepository->findOneBy($queryParam);

        if (!$asset instanceof Asset) {
            /** @var Asset $asset */
            $asset = $this->assetFactory->createNew();
            $this->entityManager->persist($asset);
            $asset->setFamilyCode($assetFamilyCode);
            $asset->setAssetCode($assetCode);
            $asset->setAttributeCode($attributeCode);
            $asset->setLocale($queryParam['locale']);
            $asset->setScope($queryParam['scope']);
            $asset->setType($this->akeneoAssetAttributePropertiesProvider->getType($assetFamilyCode, $attributeCode));
        }

        $assetAttributeValue = $this->akeneoAssetAttributeDataProvider->getData(
            $assetFamilyCode,
            $attributeCode,
            $assetAttributeResource,
            $queryParam['locale'],
            $queryParam['scope'],
        );

        $data = $this->assetAttributeValueBuilder->build(
            $assetFamilyCode,
            $attributeCode,
            $queryParam['locale'],
            $queryParam['scope'],
            $assetAttributeValue,
        );

        $asset->setContent($data);
    }
}
