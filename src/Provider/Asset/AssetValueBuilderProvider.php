<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Asset;

use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Builder\Asset\Attribute\AssetAttributeValueBuilderInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;

final class AssetValueBuilderProvider implements AssetValueBuilderProviderInterface
{
    /** @var array<AssetAttributeValueBuilderInterface> */
    private array $assetAttributeValueBuilders;

    private LoggerInterface $akeneoLogger;

    public function __construct(LoggerInterface $akeneoLogger)
    {
        $this->akeneoLogger = $akeneoLogger;
    }

    public function addBuilder(AssetAttributeValueBuilderInterface $assetAttributeValueBuilder): void
    {
        $this->assetAttributeValueBuilders[\get_class($assetAttributeValueBuilder)] = $assetAttributeValueBuilder;
    }

    /**
     * @param mixed $value
     *
     * @return mixed|null
     */
    public function build(string $assetFamilyCode, string $assetCode, ?string $locale, ?string $scope, $value)
    {
        foreach ($this->assetAttributeValueBuilders as $assetAttributeValueBuilder) {
            if ($assetAttributeValueBuilder->support($assetFamilyCode, $assetCode)) {
                return $assetAttributeValueBuilder->build($assetFamilyCode, $assetCode, $locale, $scope, $value);
            }
        }

        return null;
    }

    /**
     * @return mixed|null
     */
    public function findBuilderByClassName(string $className)
    {
        foreach ($this->assetAttributeValueBuilders as $attributeValueBuilder) {
            if (!$attributeValueBuilder instanceof $className) {
                continue;
            }

            return $attributeValueBuilder;
        }

        return null;
    }

    public function hasSupportedBuilder(string $assetFamilyCode, string $assetCode): bool
    {
        foreach ($this->assetAttributeValueBuilders as $attributeValueBuilder) {
            try {
                if ($attributeValueBuilder->support($assetFamilyCode, $assetCode)) {
                    return true;
                }
            } catch (UnsupportedAttributeTypeException $throwable) {
                $this->akeneoLogger->warning('Unsupported AssetAttributeType', [
                    'family_code' => $assetFamilyCode,
                    'asset_code' => $assetCode,
                ]);
            } catch (\Throwable $throwable) {
                $this->akeneoLogger->critical(\sprintf(
                    'AssetValueBuilderInterface "%s" failed to execute method support() for asset "%s" in family "%s"',
                    \get_class($attributeValueBuilder),
                    $assetCode,
                    $assetFamilyCode,
                ), ['exception' => $throwable]);

                return false;
            }
        }

        return false;
    }
}
