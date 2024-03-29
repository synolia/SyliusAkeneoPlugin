<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Synolia\SyliusAkeneoPlugin\Exceptions\Processor\MissingAkeneoAttributeProcessorException;
use Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute\AkeneoAttributeProcessorInterface;
use Traversable;

final class AkeneoAttributeProcessorProvider implements AkeneoAttributeProcessorProviderInterface
{
    /** @var array<AkeneoAttributeProcessorInterface> */
    private array $akeneoAttributeProcessors;

    public function __construct(Traversable $handlers)
    {
        $this->akeneoAttributeProcessors = iterator_to_array($handlers);
    }

    public function getProcessor(string $attributeCode, array $context = []): AkeneoAttributeProcessorInterface
    {
        /** @var AkeneoAttributeProcessorInterface $akeneoAttributeProcessor */
        foreach ($this->akeneoAttributeProcessors as $akeneoAttributeProcessor) {
            if ($akeneoAttributeProcessor->support($attributeCode, $context)) {
                return $akeneoAttributeProcessor;
            }
        }

        throw new MissingAkeneoAttributeProcessorException(sprintf('Could not find an AkeneoAttributeProcessor for attribute %s', $attributeCode));
    }
}
