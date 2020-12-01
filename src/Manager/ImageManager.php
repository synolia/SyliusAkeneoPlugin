<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Manager;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductMediaPayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\Product\InsertProductImagesTask;

final class ImageManager implements ImageManagerInterfce
{
    /** @var AkeneoPimClientInterface */
    private $akeneoPimClient;

    /** @var AkeneoTaskProvider */
    private $taskProvider;

    public function __construct(
        AkeneoTaskProvider $taskProvider,
        AkeneoPimClientInterface $akeneoPimClient
    ) {
        $this->taskProvider = $taskProvider;
        $this->akeneoPimClient = $akeneoPimClient;
    }

    public function updateImages(array $resource, ProductInterface $product): void
    {
        $productMediaPayload = new ProductMediaPayload($this->akeneoPimClient);
        $productMediaPayload
            ->setProduct($product)
            ->setAttributes($resource['values']);
        $imageTask = $this->taskProvider->get(InsertProductImagesTask::class);
        $imageTask->__invoke($productMediaPayload);
    }
}
