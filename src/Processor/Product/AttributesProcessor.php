<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Product;

use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Checker\AttributeOwnerChecker;
use Synolia\SyliusAkeneoPlugin\Exceptions\Processor\MissingAkeneoAttributeProcessorException;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeProcessorProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoFamilyPropertiesProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\ProductFilterRulesProviderInterface;

final class AttributesProcessor implements AttributesProcessorInterface
{
    private AkeneoFamilyPropertiesProviderInterface $akeneoFamilyPropertiesProvider;

    private ProductFilterRulesProviderInterface $productFilterRulesProvider;

    private AkeneoAttributeProcessorProviderInterface $akeneoAttributeProcessorProvider;

    private LoggerInterface $akeneoLogger;
    private AttributeOwnerChecker $attributeOwnerChecker;

    public function __construct(
        AkeneoFamilyPropertiesProviderInterface $akeneoFamilyPropertiesProvider,
        ProductFilterRulesProviderInterface $productFilterRulesProvider,
        AkeneoAttributeProcessorProviderInterface $akeneoAttributeProcessorProvider,
        LoggerInterface $akeneoLogger,
        AttributeOwnerChecker $attributeOwnerChecker
    ) {
        $this->akeneoFamilyPropertiesProvider = $akeneoFamilyPropertiesProvider;
        $this->productFilterRulesProvider = $productFilterRulesProvider;
        $this->akeneoAttributeProcessorProvider = $akeneoAttributeProcessorProvider;
        $this->akeneoLogger = $akeneoLogger;
        $this->attributeOwnerChecker = $attributeOwnerChecker;
    }

    public static function getDefaultPriority(): int
    {
        return 800;
    }

    public function process(ProductInterface $product, array $resource): void
    {
        $filters = $this->productFilterRulesProvider->getProductFiltersRules();
        $family = $this->akeneoFamilyPropertiesProvider->getProperties($resource['family']);

        foreach ($resource['values'] as $attributeCode => $translations) {
            if ($family['attribute_as_label'] === $attributeCode) {
                continue;
            }

            // Skip attribute if not part of the model
            if (!$this->attributeOwnerChecker->isAttributePartOfModel($resource, $attributeCode)) {
                $this->akeneoLogger->info('Skipped attribute insertion on product', [
                    'product' => $resource['code'] ?? $resource['identifier'],
                    'attribute_code' => $attributeCode,
                ]);

                continue;
            }

            try {
                $context = [
                    'calledBy' => $this,
                    'model' => $product,
                    'scope' => $filters->getChannel(),
                    'data' => $translations,
                ];

                $processor = $this->akeneoAttributeProcessorProvider->getProcessor((string) $attributeCode, $context);
                $processor->process((string) $attributeCode, $context);
            } catch (MissingAkeneoAttributeProcessorException $missingAkeneoAttributeProcessorException) {
                $this->akeneoLogger->debug($missingAkeneoAttributeProcessorException->getMessage());
            }
        }
    }

    public function support(ProductInterface $product, array $resource): bool
    {
        return \count($resource['values']) > 0;
    }
}
