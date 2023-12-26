<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\Asset;
use Synolia\SyliusAkeneoPlugin\Provider\Asset\AkeneoAssetAttributeDataProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Asset\AkeneoAssetAttributePropertiesProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Asset\AssetValueBuilderProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\ProductFilterRulesProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;

final class AssetProductAttributeProcessor implements AssetProductAttributeProcessorInterface
{
    public function __construct(
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
        ProductInterface $model,
        string $assetFamilyCode,
        string $assetCode,
        string $attributeCode,
        array $assetAttributeResource = [],
    ): bool {
        if (!$this->assetAttributeValueBuilder->hasSupportedBuilder($assetFamilyCode, $attributeCode)) {
            return false;
        }

        if (!\method_exists($model, 'addAsset')) {
            return false;
        }

        return true;
    }

    public function process(
        ProductInterface $model,
        string $assetFamilyCode,
        string $assetCode,
        string $attributeCode,
        array $assetAttributeResource = [],
    ): void {
        if (!$this->support($model, $assetFamilyCode, $assetCode, $attributeCode)) {
            return;
        }

        $scope = $this->productFilterRulesProvider->getProductFiltersRules()->getChannel();
        $queryParam = [
            'familyCode' => $assetFamilyCode,
            'attributeCode' => $attributeCode,
            'assetCode' => $assetCode,
            'scope' => $scope,
        ];

        foreach ($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms() as $syliusLocale) {
            $queryParam['locale'] = $syliusLocale;
            $this->handleAsset($model, $assetFamilyCode, $assetCode, $attributeCode, $queryParam, $assetAttributeResource);
        }
    }

    private function handleAsset(
        ProductInterface $model,
        string $assetFamilyCode,
        string $assetCode,
        string $attributeCode,
        array $queryParam,
        array $assetAttributeResource,
    ): void {
        if (!\method_exists($model, 'addAsset')) {
            return;
        }

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

        $akeneoLocale = $this->syliusAkeneoLocaleCodeProvider->getAkeneoLocale($queryParam['locale']);

        $assetAttributeValue = $this->akeneoAssetAttributeDataProvider->getData(
            $assetFamilyCode,
            $attributeCode,
            $assetAttributeResource,
            $akeneoLocale,
            $queryParam['scope'],
        );

        /** @var array $data */
        $data = $this->assetAttributeValueBuilder->build(
            $assetFamilyCode,
            $attributeCode,
            $akeneoLocale,
            $queryParam['scope'],
            $assetAttributeValue,
        );

        $asset->setContent($data);
        $model->addAsset($asset);
    }
}
