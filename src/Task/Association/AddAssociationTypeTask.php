<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Association;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

class AddAssociationTypeTask implements AkeneoTaskInterface
{
    /** @var FactoryInterface */
    private $productAssociationTypeFactory;

    public function __construct(FactoryInterface $productAssociationTypeFactory)
    {
        $this->productAssociationTypeFactory = $productAssociationTypeFactory;
    }

    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload->getResources() instanceof ResourceCursorInterface) {
            throw new NoAttributeResourcesException('No resource found.');
        }

        foreach ($payload->getResources() as $resource) {
            $productAssociationType = $this->productAssociationTypeFactory->createNew();
            if (!$productAssociationType instanceof ProductAssociationTypeInterface) {
                throw new \LogicException('Unknown error.');
            }
        }
    }
}