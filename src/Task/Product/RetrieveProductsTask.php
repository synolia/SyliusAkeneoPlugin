<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Akeneo\Pim\ApiClient\Pagination\Page;
use Doctrine\Common\Collections\Collection;
use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Filter\ProductFilter;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Provider\ConfigurationProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class RetrieveProductsTask implements AkeneoTaskInterface
{
    /** @var LoggerInterface */
    private $logger;

    /** @var ConfigurationProvider */
    private $configurationProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\Filter\ProductFilter */
    private $productFilter;

    public function __construct(
        LoggerInterface $akeneoLogger,
        ConfigurationProvider $configurationProvider,
        ProductFilter $productFilter
    ) {
        $this->logger = $akeneoLogger;
        $this->configurationProvider = $configurationProvider;
        $this->productFilter = $productFilter;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload instanceof ProductPayload) {
            return $payload;
        }

        $this->logger->debug(self::class);
        $this->logger->notice(Messages::retrieveFromAPI($payload->getType()));

        $queryParameters = $this->productFilter->getProductFilters();

        /** @var \Akeneo\Pim\ApiClient\Pagination\PageInterface|null $resources */
        $resources = $payload->getAkeneoPimClient()->getProductApi()->listPerPage(
            $this->configurationProvider->getConfiguration()->getPaginationSize(),
            true,
            ['search' => $queryParameters]
        );

        if (!$resources instanceof Page) {
            return $payload;
        }

        $itemCount = 0;

        while (
            ($resources instanceof Page && $resources->hasNextPage()) ||
            ($resources instanceof Page && !$resources->hasPreviousPage()) ||
            $resources instanceof Page
        ) {
            foreach ($resources->getItems() as $item) {
                $this->handleSimpleProduct($payload->getSimpleProductPayload()->getProducts(), $item);
                $this->handleConfigurableProduct($payload->getConfigurableProductPayload()->getProducts(), $item);
                ++$itemCount;
            }

            $resources = $resources->getNextPage();
        }

        $this->logger->info(Messages::totalToImport($payload->getType(), $itemCount));

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
