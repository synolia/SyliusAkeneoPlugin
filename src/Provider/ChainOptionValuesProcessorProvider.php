<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Synolia\SyliusAkeneoPlugin\Exceptions\Processor\MissingProductOptionValuesProcessorException;
use Synolia\SyliusAkeneoPlugin\Processor\ProductOptionValue\OptionValuesProcessorInterface;

final class ChainOptionValuesProcessorProvider implements OptionValuesProcessorProviderInterface
{
    public function __construct(
        /** @var iterable<OptionValuesProcessorInterface> $optionValuesProcessors */
        #[AutowireIterator(OptionValuesProcessorInterface::TAG_ID)]
        private iterable $optionValuesProcessors,
    ) {
    }

    /**
     * @throws MissingProductOptionValuesProcessorException
     */
    public function getProcessor(
        AttributeInterface $attribute,
        ProductOptionInterface $productOption,
        array $context = [],
    ): OptionValuesProcessorInterface {
        /** @var OptionValuesProcessorInterface $optionValuesProcessor */
        foreach ($this->optionValuesProcessors as $optionValuesProcessor) {
            if ($optionValuesProcessor->support($attribute, $productOption, $context)) {
                return $optionValuesProcessor;
            }
        }

        throw new MissingProductOptionValuesProcessorException(sprintf('Could not find an OptionValuesProcessor for option %s', $productOption->getCode()));
    }
}
