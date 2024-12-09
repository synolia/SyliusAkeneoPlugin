<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\Asset;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationOrScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingScopeException;
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
        private LoggerInterface $akeneoLogger,
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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function handleAsset(
        ProductInterface $model,
        string $assetFamilyCode,
        string $assetCode,
        string $attributeCode,
        array $queryParam,
        array $assetAttributeResource,
    ): void {
        if (!\method_exists($model, 'addAsset') || !\method_exists($model, 'getAssets')) {
            return;
        }

        $akeneoLocale = $this->syliusAkeneoLocaleCodeProvider->getAkeneoLocale($queryParam['locale']);

        try {
            $asset = $this->assetRepository->findOneBy($queryParam);

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

            if (!$asset instanceof Asset) {
                $this->akeneoLogger->debug('Created asset for product', [
                    'product' => $model->getCode(),
                    'familyCode' => $assetFamilyCode,
                    'attributeCode' => $attributeCode,
                    'assetCode' => $assetCode,
                    'locale' => $queryParam['locale'],
                    'content' => $data,
                ]);

                /** @var Asset $asset */
                $asset = $this->assetFactory->createNew();
                $this->entityManager->persist($asset);
                $asset->setFamilyCode($assetFamilyCode);
                $asset->setAssetCode($assetCode);
                $asset->setAttributeCode($attributeCode);
                $asset->setLocale($queryParam['locale']);
                $asset->setScope($queryParam['scope']);
                $asset->setType($this->akeneoAssetAttributePropertiesProvider->getType($assetFamilyCode, $attributeCode));
                $asset->setContent($data);
                $this->addAssetToProduct(
                    $model,
                    $asset,
                    $assetFamilyCode,
                    $attributeCode,
                    $assetCode,
                    $queryParam,
                    $data,
                );

                return;
            }

            $oldContent = $asset->getContent();

            if ($oldContent === $data) {
                $this->akeneoLogger->debug('Skipped asset for product as it has same content', [
                    'product' => $model->getCode(),
                    'familyCode' => $assetFamilyCode,
                    'attributeCode' => $attributeCode,
                    'assetCode' => $assetCode,
                    'locale' => $queryParam['locale'],
                    'content' => $data,
                ]);

                $this->addAssetToProduct(
                    $model,
                    $asset,
                    $assetFamilyCode,
                    $attributeCode,
                    $assetCode,
                    $queryParam,
                    $data,
                );

                return;
            }

            $asset->setContent($data);
            $this->addAssetToProduct(
                $model,
                $asset,
                $assetFamilyCode,
                $attributeCode,
                $assetCode,
                $queryParam,
                $data,
            );

            $this->akeneoLogger->debug('Updated asset for product', [
                'product' => $model->getCode(),
                'familyCode' => $assetFamilyCode,
                'attributeCode' => $attributeCode,
                'assetCode' => $assetCode,
                'locale' => $queryParam['locale'],
                'old_content' => $oldContent,
                'content' => $data,
            ]);
        } catch (MissingLocaleTranslationException|MissingLocaleTranslationOrScopeException|MissingScopeException $e) {
            $this->akeneoLogger->debug('Error processing asset', [
                'product' => $model->getCode(),
                'family_code' => $assetFamilyCode,
                'attribute_code' => $attributeCode,
                'asset_code' => $assetCode,
                'scope' => $queryParam['scope'],
                'akeneo_locale' => $akeneoLocale,
                'resource' => $assetAttributeResource,
                'exception' => $e->getMessage(),
                'exception_type' => $e::class,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function addAssetToProduct(
        ProductInterface $model,
        Asset $asset,
        string $assetFamilyCode,
        string $attributeCode,
        string $assetCode,
        array $queryParam,
        array $data,
    ): void {
        if (!\method_exists($model, 'addAsset') || !\method_exists($model, 'getAssets')) {
            return;
        }

        if ($model->getAssets()->contains($asset)) {
            $this->akeneoLogger->debug('Asset already associated to product', [
                'product' => $model->getCode(),
                'familyCode' => $assetFamilyCode,
                'attributeCode' => $attributeCode,
                'assetCode' => $assetCode,
                'locale' => $queryParam['locale'],
                'content' => $data,
            ]);

            return;
        }

        $model->addAsset($asset);

        $this->akeneoLogger->debug('Associated asset to product', [
            'product' => $model->getCode(),
            'familyCode' => $assetFamilyCode,
            'attributeCode' => $attributeCode,
            'assetCode' => $assetCode,
            'locale' => $queryParam['locale'],
            'content' => $data,
        ]);
    }
}
