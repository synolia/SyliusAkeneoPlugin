<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Product;

use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\Processor\MissingAkeneoAttributeProcessorException;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeProcessorProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoFamilyPropertiesProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\ProductFilterRulesProviderInterface;

final class AttributesProcessor implements AttributesProcessorInterface
{
    public function __construct(
        private AkeneoFamilyPropertiesProviderInterface $akeneoFamilyPropertiesProvider,
        private ProductFilterRulesProviderInterface $productFilterRulesProvider,
        private AkeneoAttributeProcessorProviderInterface $akeneoAttributeProcessorProvider,
        private LoggerInterface $akeneoLogger,
    ) {
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
        return (is_countable($resource['values']) ? \count($resource['values']) : 0) > 0;
    }
}
