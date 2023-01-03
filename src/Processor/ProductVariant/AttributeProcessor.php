<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductVariant;

use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoProductFiltersConfigurationException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Processor\MissingAkeneoAttributeProcessorException;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeProcessorProviderInterface;
use Synolia\SyliusAkeneoPlugin\Repository\ProductFiltersRulesRepository;

class AttributeProcessor implements AttributeProcessorInterface
{
    private ?ProductFiltersRules $productFiltersRules = null;

    public function __construct(private AkeneoAttributeProcessorProviderInterface $akeneoAttributeProcessorProvider, private ProductFiltersRulesRepository $productFiltersRulesRepository, private LoggerInterface $logger)
    {
    }

    public function process(ProductVariantInterface $productVariant, array $resource): void
    {
        $scope = $this->getProductFilterRules()->getChannel();

        foreach ($resource['values'] as $attributeCode => $values) {
            try {
                $processor = $this->akeneoAttributeProcessorProvider->getProcessor((string) $attributeCode, [
                    'calledBy' => $this,
                    'model' => $productVariant,
                    'scope' => $scope,
                    'data' => $values,
                ]);
                $processor->process((string) $attributeCode, [
                    'calledBy' => $this,
                    'model' => $productVariant,
                    'scope' => $scope,
                    'data' => $values,
                ]);
            } catch (MissingAkeneoAttributeProcessorException $missingAkeneoAttributeProcessorException) {
                $this->logger->debug($missingAkeneoAttributeProcessorException->getMessage());
            }
        }
    }

    /**
     * @throws NoProductFiltersConfigurationException
     */
    private function getProductFilterRules(): ProductFiltersRules
    {
        if ($this->productFiltersRules instanceof ProductFiltersRules) {
            return $this->productFiltersRules;
        }

        $filters = $this->productFiltersRulesRepository->findOneBy([], ['id' => 'DESC']);

        if (!$filters instanceof ProductFiltersRules) {
            throw new NoProductFiltersConfigurationException('Product filters must be configured before importing product attributes.');
        }

        return $this->productFiltersRules = $filters;
    }

    public function support(ProductVariantInterface $productVariant, array $resource): bool
    {
        try {
            return
                null !== $this->getProductFilterRules() &&
                is_array($resource['values']) &&
                count($resource['values']) > 0
            ;
        } catch (NoProductFiltersConfigurationException) {
            return false;
        }
    }
}
