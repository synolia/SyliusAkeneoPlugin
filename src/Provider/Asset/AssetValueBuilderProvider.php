<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Asset;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Synolia\SyliusAkeneoPlugin\Builder\Asset\Attribute\AssetAttributeValueBuilderInterface;
use Synolia\SyliusAkeneoPlugin\Checker\EditionCheckerInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;

final class AssetValueBuilderProvider implements AssetValueBuilderProviderInterface
{
    public function __construct(
        /** @var iterable<AssetAttributeValueBuilderInterface> $assetAttributeValueBuilders */
        #[AutowireIterator(AssetAttributeValueBuilderInterface::class)]
        private iterable $assetAttributeValueBuilders,
        private LoggerInterface $akeneoLogger,
        private EditionCheckerInterface $editionChecker,
    ) {
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

    public function findBuilderByClassName(string $className): ?AssetAttributeValueBuilderInterface
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
        $isEnterprise = $this->editionChecker->isEnterprise() || $this->editionChecker->isSerenityEdition();

        if (!$isEnterprise) {
            return false;
        }

        foreach ($this->assetAttributeValueBuilders as $attributeValueBuilder) {
            try {
                if ($attributeValueBuilder->support($assetFamilyCode, $assetCode)) {
                    return true;
                }
            } catch (UnsupportedAttributeTypeException) {
                $this->akeneoLogger->warning('Unsupported AssetAttributeType', [
                    'family_code' => $assetFamilyCode,
                    'asset_code' => $assetCode,
                ]);
            } catch (\Throwable $throwable) {
                $this->akeneoLogger->error(\sprintf(
                    'AssetValueBuilderInterface "%s" failed to execute method support() for asset "%s" in family "%s"',
                    $attributeValueBuilder::class,
                    $assetCode,
                    $assetFamilyCode,
                ), ['exception' => $throwable]);

                return false;
            }
        }

        return false;
    }
}
