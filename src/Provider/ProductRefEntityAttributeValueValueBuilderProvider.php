<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Synolia\SyliusAkeneoPlugin\Exceptions\Processor\MissingAkeneoAttributeProcessorException;
use Synolia\SyliusAkeneoPlugin\Processor\ProductAttributeValue\ReferenceEntity\ReferenceEntityAttributeValueProcessorInterface;

final class ProductRefEntityAttributeValueValueBuilderProvider implements ProductRefEntityAttributeValueValueBuilderProviderInterface
{
    public function __construct(
        /** @var iterable<ReferenceEntityAttributeValueProcessorInterface> $referenceEntityAttributeValueProcessors */
        #[AutowireIterator(ReferenceEntityAttributeValueProcessorInterface::TAG_ID)]
        private iterable $referenceEntityAttributeValueProcessors,
    ) {
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
