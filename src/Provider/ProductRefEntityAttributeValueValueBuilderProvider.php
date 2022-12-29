<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Synolia\SyliusAkeneoPlugin\Exceptions\Processor\MissingAkeneoAttributeProcessorException;
use Synolia\SyliusAkeneoPlugin\Processor\ProductAttributeValue\ReferenceEntity\ReferenceEntityAttributeValueProcessorInterface;
use Traversable;

final class ProductRefEntityAttributeValueValueBuilderProvider implements ProductRefEntityAttributeValueValueBuilderProviderInterface
{
    /** @var array<ReferenceEntityAttributeValueProcessorInterface> */
    private array $referenceEntityAttributeValueProcessors;

    public function __construct(Traversable $handlers)
    {
        $this->referenceEntityAttributeValueProcessors = iterator_to_array($handlers);
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessor(
        string $attributeCode,
        string $referenceEntityCode,
        string $subAttributeCode,
        string $locale,
        string $scope,
        $value,
        array $context = [],
    ): ReferenceEntityAttributeValueProcessorInterface {
        /** @var ReferenceEntityAttributeValueProcessorInterface $akeneoAttributeProcessor */
        foreach ($this->referenceEntityAttributeValueProcessors as $akeneoAttributeProcessor) {
            if ($akeneoAttributeProcessor->support($attributeCode, $referenceEntityCode, $subAttributeCode, $locale, $scope, $value, $context)) {
                return $akeneoAttributeProcessor;
            }
        }

        throw new MissingAkeneoAttributeProcessorException(sprintf('Could not find an AkeneoAttributeProcessor for attribute %s', $attributeCode));
    }
}
