<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\ProductAttributeValue;

use Sylius\Component\Product\Model\ProductAttributeInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\Processor\MissingAkeneoProductAttributeValueProcessorException;
use Synolia\SyliusAkeneoPlugin\Processor\ProductAttributeValue\Table\TableProductAttributeValueProcessorInterface;

interface TableAttributeValueProcessorProviderInterface
{
    /**
     * @throws MissingAkeneoProductAttributeValueProcessorException
     *
     * @param mixed $value
     */
    public function getProcessor(
        ProductAttributeInterface $attribute,
        array $tableConfiguration,
        string $locale,
        ?string $scope,
        $value,
        array $context = [],
    ): TableProductAttributeValueProcessorInterface;
}
