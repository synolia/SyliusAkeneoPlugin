<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Synolia\SyliusAkeneoPlugin\Exceptions\Processor\MissingAkeneoAttributeProcessorException;
use Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute\AkeneoAttributeProcessorInterface;

final class AkeneoAttributeProcessorProvider implements AkeneoAttributeProcessorProviderInterface
{
    public function __construct(
        /** @var iterable<AkeneoAttributeProcessorInterface> $akeneoAttributeProcessors */
        #[AutowireIterator(AkeneoAttributeProcessorInterface::class)]
        private iterable $akeneoAttributeProcessors,
    ) {
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
