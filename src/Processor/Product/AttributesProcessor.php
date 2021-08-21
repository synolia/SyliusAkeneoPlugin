<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Product;

use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\Processor\MissingAkeneoAttributeProcessorException;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeProcessorProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoFamilyPropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Provider\ProductFilterRulesProviderInterface;

class AttributesProcessor implements AttributesProcessorInterface
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoFamilyPropertiesProvider */
    private $akeneoFamilyPropertiesProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\ProductFilterRulesProviderInterface */
    private $productFilterRulesProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeProcessorProviderInterface */
    private $akeneoAttributeProcessorProvider;

    /** @var \Psr\Log\LoggerInterface */
    private $akeneoLogger;

    public function __construct(
        AkeneoFamilyPropertiesProvider $akeneoFamilyPropertiesProvider,
        ProductFilterRulesProviderInterface $productFilterRulesProvider,
        AkeneoAttributeProcessorProviderInterface $akeneoAttributeProcessorProvider,
        LoggerInterface $akeneoLogger
    ) {
        $this->akeneoFamilyPropertiesProvider = $akeneoFamilyPropertiesProvider;
        $this->productFilterRulesProvider = $productFilterRulesProvider;
        $this->akeneoAttributeProcessorProvider = $akeneoAttributeProcessorProvider;
        $this->akeneoLogger = $akeneoLogger;
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
}
