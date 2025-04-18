<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\ProductAttributeValue;

use Sylius\Component\Product\Model\ProductAttributeInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Synolia\SyliusAkeneoPlugin\Exceptions\Processor\MissingAkeneoProductAttributeValueProcessorException;
use Synolia\SyliusAkeneoPlugin\Processor\ProductAttributeValue\Table\TableProductAttributeValueProcessorInterface;

class TableAttributeValueProcessorProvider implements TableAttributeValueProcessorProviderInterface
{
    public function __construct(
        #[AutowireIterator(TableProductAttributeValueProcessorInterface::TAG_ID)]
        private iterable $tableAttributeValueProcessors,
    ) {
    }

    /**
     * @throws MissingAkeneoProductAttributeValueProcessorException
     */
    public function getProcessor(
        ProductAttributeInterface $attribute,
        array $tableConfiguration,
        string $locale,
        ?string $scope,
        $value,
        array $context = [],
    ): TableProductAttributeValueProcessorInterface {
        /** @var TableProductAttributeValueProcessorInterface $akeneoAttributeProcessor */
        foreach ($this->tableAttributeValueProcessors as $akeneoAttributeProcessor) {
            if ($akeneoAttributeProcessor->support($attribute, $tableConfiguration, $locale, $scope, $value, $context)) {
                return $akeneoAttributeProcessor;
            }
        }

        throw new MissingAkeneoProductAttributeValueProcessorException(sprintf('Could not find processor for attribute value %s', $attribute->getCode()));
    }
}
