<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ProductModel;

use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class RetrieveProductModelsTask implements AkeneoTaskInterface
{
    /**
     * @param ProductModelPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $resources = $payload->getAkeneoPimClient()->getProductModelApi()->all();

        $payload = new ProductModelPayload($payload->getAkeneoPimClient());
        $payload->setResources($resources);

        return $payload;
    }
}
