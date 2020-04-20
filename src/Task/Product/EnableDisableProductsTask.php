<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Repository\ProductRepository;
use Synolia\SyliusAkeneoPlugin\Service\ProductChannelEnabler;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class EnableDisableProductsTask implements AkeneoTaskInterface
{
    /** @var \Synolia\SyliusAkeneoPlugin\Repository\ProductRepository */
    private $productRepository;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var \Synolia\SyliusAkeneoPlugin\Service\ProductChannelEnabler */
    private $productChannelEnabler;

    public function __construct(
        ProductRepository $productRepository,
        LoggerInterface $akeneoLogger,
        ProductChannelEnabler $productChannelEnabler
    ) {
        $this->productRepository = $productRepository;
        $this->logger = $akeneoLogger;
        $this->productChannelEnabler = $productChannelEnabler;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload instanceof ProductPayload) {
            return $payload;
        }

        foreach ($payload->getSimpleProductPayload()->getProducts() as $resource) {
            try {
                /** @var ProductInterface $product */
                $product = $this->productRepository->findOneBy(['code' => $resource['identifier']]);

                if (!$product instanceof ProductInterface) {
                    continue;
                }

                $this->productChannelEnabler->enableChannelForProduct($product, $resource);
            } catch (\Throwable $throwable) {
                $this->logger->warning($throwable->getMessage());
            }
        }

        return $payload;
    }
}
