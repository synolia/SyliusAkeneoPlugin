<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ProductModel;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoProductModelResourcesException;
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class DisableProductModelsTask implements AkeneoTaskInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    public function __construct(EntityManagerInterface $entityManager, ProductRepositoryInterface $productRepository)
    {
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
    }

    /**
     * @param ProductModelPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload->getResources() instanceof ResourceCursorInterface) {
            throw new NoProductModelResourcesException('No resource found.');
        }

        try {
            $resourcesMapping = [];
            foreach ($payload->getResources() as $resource) {
                $resourcesMapping[$resource['code']] = $resource;
            }

            $this->entityManager->beginTransaction();
            $products = $this->productRepository->findAll();
            /** @var ProductInterface $product */
            foreach ($products as $product) {
                if (isset($resourcesMapping[$product->getCode()])) {
                    continue;
                }
                $product->disable();
                $payload->getResources()->next();
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $throwable) {
            $this->entityManager->rollback();

            throw $throwable;
        }

        return $payload;
    }
}
