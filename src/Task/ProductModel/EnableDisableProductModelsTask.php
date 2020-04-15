<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ProductModel;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Repository\ProductRepository;
use Synolia\SyliusAkeneoPlugin\Service\ProductChannelEnabler;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class EnableDisableProductModelsTask implements AkeneoTaskInterface
{
    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    /** @var \Synolia\SyliusAkeneoPlugin\Repository\ProductRepository */
    private $productRepository;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var \Synolia\SyliusAkeneoPlugin\Service\ProductChannelEnabler */
    private $productChannelEnabler;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProductRepository $productRepository,
        LoggerInterface $akeneoLogger,
        ProductChannelEnabler $productChannelEnabler
    ) {
        $this->entityManager = $entityManager;
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
                $this->entityManager->beginTransaction();

                /** @var ProductInterface $product */
                $product = $this->productRepository->findOneBy(['code' => $resource['code']]);

                if (!$product instanceof ProductInterface) {
                    continue;
                }

                $this->productChannelEnabler->enableChannelForProduct($product, $resource);

                $this->entityManager->flush();
                $this->entityManager->commit();
            } catch (\Throwable $throwable) {
                $this->logger->warning($throwable->getMessage());
                if ($this->entityManager->getConnection()->isTransactionActive()) {
                    $this->entityManager->rollback();
                }
            }
        }

        return $payload;
    }
}
