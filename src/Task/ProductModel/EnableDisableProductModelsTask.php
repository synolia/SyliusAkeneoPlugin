<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ProductModel;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Repository\ProductRepository;
use Synolia\SyliusAkeneoPlugin\Service\ProductChannelEnabler;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class EnableDisableProductModelsTask implements AkeneoTaskInterface
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
     * @param \Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload instanceof ProductModelPayload) {
            return $payload;
        }

        if (!$payload->getResources() instanceof ResourceCursorInterface) {
            return $payload;
        }

        foreach ($payload->getResources() as $resource) {
            try {
                /** @var ProductInterface $product */
                $product = $this->productRepository->findOneBy(['code' => $resource['code']]);

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
