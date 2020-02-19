<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Category;

use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class RetrieveCategoriesTask implements AkeneoTaskInterface
{
    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $resources = $payload->getAkeneoPimClient()->getCategoryApi()->all();

        $payload = new CategoryPayload($payload->getAkeneoPimClient());
        $payload->setResources($resources);

        return $payload;
    }
}
