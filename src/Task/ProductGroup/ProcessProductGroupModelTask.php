<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ProductGroup;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroupInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;
use Synolia\SyliusAkeneoPlugin\Repository\ProductGroupRepository;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

class ProcessProductGroupModelTask implements AkeneoTaskInterface
{
    private array $productGroups;

    public function __construct(
        private ApiConnectionProviderInterface $apiConnectionProvider,
        private ProductGroupRepository $productGroupRepository,
        private ProductRepositoryInterface $productRepository,
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
    ) {
        $this->productGroups = [];
    }

    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $resourceCursor = $payload->getAkeneoPimClient()->getProductModelApi()->all(
            $this->apiConnectionProvider->get()->getPaginationSize(),
        );

        /**
         * @var array{
         *     parent: string|null,
         *     code: string,
         * } $resource
         */
        foreach ($resourceCursor as $resource) {
            if (null === $resource['parent']) {
                continue;
            }

            /** @var ProductGroupInterface|null $productGroup */
            $productGroup = $this->productGroupRepository->findOneBy(['model' => $resource['parent']]);

            if (!$productGroup instanceof ProductGroupInterface) {
                continue;
            }

            /** @var ProductInterface|null $product */
            $product = $this->productRepository->findOneByCode($resource['code']);

            if (!$product instanceof ProductInterface) {
                continue;
            }

            if (!\array_key_exists($productGroup->getModel(), $this->productGroups)) {
                $this->productGroups[$productGroup->getModel()] = true;
                $productGroup->getProducts()->clear();

                $this->logger->info('Cleaned ProductGroup associations', [
                    'product_code' => $product->getCode(),
                    'product_group_id' => $productGroup->getId(),
                    'family' => $productGroup->getFamily(),
                ]);
            }

            $productGroup->addProduct($product);
            $this->entityManager->flush();

            $this->logger->info('Added product to ProductGroup association', [
                'product_code' => $product->getCode(),
                'product_group_id' => $productGroup->getId(),
                'family' => $productGroup->getFamily(),
            ]);
        }

        return $payload;
    }
}
