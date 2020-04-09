<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Akeneo\Pim\ApiClient\Pagination\Page;
use Doctrine\Common\Collections\Collection;
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class RetrieveProductsTask implements AkeneoTaskInterface
{
    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload instanceof ProductPayload) {
            return $payload;
        }

        /** @var \Akeneo\Pim\ApiClient\Pagination\PageInterface|null $resources */
        $resources = $payload->getAkeneoPimClient()->getProductApi()->listPerPage(100, true);

        if (!$resources instanceof Page) {
            return $payload;
        }

        while (
            ($resources instanceof Page && $resources->hasNextPage()) ||
            ($resources instanceof Page && !$resources->hasPreviousPage()) ||
            $resources instanceof Page
        ) {
            foreach ($resources->getItems() as $item) {
                $this->handleSimpleProduct($payload->getSimpleProductPayload()->getProducts(), $item);
                $this->handleConfigurableProduct($payload->getConfigurableProductPayload()->getProducts(), $item);
            }

            $resources = $resources->getNextPage();
        }

        return $payload;
    }

    private function handleSimpleProduct(Collection $products, array $item): void
    {
        if ($item['parent'] !== null) {
            return;
        }

        $products->add($item);
    }

    private function handleConfigurableProduct(Collection $products, array $item): void
    {
        if ($item['parent'] === null) {
            return;
        }

        $products->add($item);
    }
}
