<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Exception\RuntimeException;
use Psr\Log\LoggerInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Checker\EditionCheckerInterface;
use Synolia\SyliusAkeneoPlugin\Component\Attribute\AttributeType\AssetAttributeType;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationOrScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\TranslationNotFoundException;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\Provider\Data\AkeneoAttributeDataProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Transformer\AkeneoAttributeToSyliusAttributeTransformerInterface;
use Webmozart\Assert\Assert;

/**
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
final class AssetAttributeProcessor implements AkeneoAttributeProcessorInterface
{
    public function __construct(
        private SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        private AkeneoAttributeToSyliusAttributeTransformerInterface $akeneoAttributeToSyliusAttributeTransformer,
        private RepositoryInterface $productAttributeRepository,
        private LoggerInterface $akeneoLogger,
        private RepositoryInterface $productAttributeValueRepository,
        private FactoryInterface $productAttributeValueFactory,
        private AkeneoAttributeDataProviderInterface $akeneoAttributeDataProvider,
        private AkeneoPimClientInterface $akeneoPimClient,
        private AssetProductAttributeProcessorInterface $akeneoAssetProductAttributeProcessor,
        private EditionCheckerInterface $editionChecker,
    ) {
    }

    public static function getDefaultPriority(): int
    {
        return 100;
    }

    public function support(string $attributeCode, array $context = []): bool
    {
        $isEnterprise = $this->editionChecker->isEnterprise() || $this->editionChecker->isSerenityEdition();

        if (!$isEnterprise) {
            return false;
        }

        if (!$context['model'] instanceof ProductInterface) {
            return false;
        }

        if (!\method_exists($context['model'], 'getAssets')) {
            return false;
        }

        $transformedAttributeCode = $this->akeneoAttributeToSyliusAttributeTransformer->transform($attributeCode);

        /** @var AttributeInterface $attribute */
        $attribute = $this->productAttributeRepository->findOneBy(['code' => $transformedAttributeCode]);

        if ($attribute instanceof AttributeInterface && $attribute->getType() === AssetAttributeType::TYPE) {
            return true;
        }

        return false;
    }

    public function process(string $attributeCode, array $context = []): void
    {
        $this->akeneoLogger->debug(\sprintf(
            'Attribute "%s" is being processed by "%s"',
            $attributeCode,
            static::class,
        ));

        Assert::isInstanceOf($context['model'], ProductInterface::class);

        $transformedAttributeCode = $this->akeneoAttributeToSyliusAttributeTransformer->transform($attributeCode);

        /** @var AttributeInterface $attribute */
        $attribute = $this->productAttributeRepository->findOneBy(['code' => $transformedAttributeCode]);

        foreach ($context['data'] as $translation) {
            if (null !== $translation['locale'] && false === $this->syliusAkeneoLocaleCodeProvider->isActiveLocale($translation['locale'])) {
                continue;
            }

            if (null === $translation['locale']) {
                foreach ($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms() as $syliusLocale) {
                    try {
                        $this->setAttributeTranslation(
                            $context['model'],
                            $attribute,
                            $context['data'],
                            $syliusLocale,
                            $attributeCode,
                            $context['scope'],
                        );
                    } catch (TranslationNotFoundException | MissingScopeException | MissingLocaleTranslationOrScopeException | MissingLocaleTranslationException) {
                    }
                }

                continue;
            }

            try {
                $this->setAttributeTranslation(
                    $context['model'],
                    $attribute,
                    $context['data'],
                    $translation['locale'],
                    $attributeCode,
                    $context['scope'],
                );
            } catch (TranslationNotFoundException | MissingScopeException | MissingLocaleTranslationOrScopeException | MissingLocaleTranslationException) {
            }
        }

        foreach ($context['data'] as $assetCodes) {
            foreach ($assetCodes['data'] as $assetCode) {
                try {
                    $assetResource = $this->akeneoPimClient->getAssetManagerApi()->get($attributeCode, $assetCode);
                    $this->handleAssetByFamilyResource($context['model'], $attributeCode, $assetResource);
                } catch (RuntimeException $runtimeException) {
                    $this->akeneoLogger->error('Error processing asset', [
                        'product' => $context['model']->getCode(),
                        'asset_code' => $assetCode,
                        'exception_code' => $runtimeException->getCode(),
                        'exception_message' => $runtimeException->getMessage(),
                        'exception_trace' => $runtimeException->getTraceAsString(),
                    ]);
                }
            }
        }
    }

    private function handleAssetByFamilyResource(
        ProductInterface $model,
        string $assetFamilyCode,
        array $assetResource,
    ): void {
        foreach ($assetResource['values'] as $attributeCode => $assetAttributeResource) {
            try {
                $this->akeneoAssetProductAttributeProcessor->process(
                    $model,
                    $assetFamilyCode,
                    $assetResource['code'],
                    $attributeCode,
                    $assetAttributeResource,
                );
            } catch (UnsupportedAttributeTypeException $attributeTypeException) {
                $this->akeneoLogger->warning('Unsupported attribute type', ['ex' => $attributeTypeException]);
            }
        }
    }

    /**
     * @throws MissingLocaleTranslationOrScopeException
     * @throws MissingLocaleTranslationException
     * @throws MissingScopeException
     * @throws TranslationNotFoundException
     */
    private function setAttributeTranslation(
        ProductInterface $product,
        AttributeInterface $attribute,
        array $translations,
        string $locale,
        string $attributeCode,
        string $scope,
    ): void {
        $attributeValue = $this->productAttributeValueRepository->findOneBy([
            'subject' => $product,
            'attribute' => $attribute,
            'localeCode' => $locale,
        ]);

        if (!$attributeValue instanceof ProductAttributeValueInterface) {
            /** @var ProductAttributeValueInterface $attributeValue */
            $attributeValue = $this->productAttributeValueFactory->createNew();
        }

        $attributeValue->setLocaleCode($locale);
        $attributeValue->setAttribute($attribute);
        $attributeValueValue = $this->akeneoAttributeDataProvider->getData($attributeCode, $translations, $locale, $scope);
        $attributeValue->setValue($attributeValueValue);
        $product->addAttribute($attributeValue);
    }
}
