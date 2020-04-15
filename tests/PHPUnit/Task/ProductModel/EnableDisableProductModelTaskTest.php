<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\ProductModel;

use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\Product;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\AddOrUpdateProductModelTask;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\EnableDisableProductModelsTask;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\RetrieveProductModelsTask;

final class EnableDisableProductModelTaskTest extends AbstractTaskTest
{
    /** @var AkeneoTaskProvider */
    private $taskProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskProvider = self::$container->get(AkeneoTaskProvider::class);
        self::assertInstanceOf(AkeneoTaskProvider::class, $this->taskProvider);
    }

    public function testEnableDisableProductModelTask(): void
    {
        $this->createProductConfiguration();

        $productModelPayload = new ProductModelPayload($this->createClient());

        /** @var RetrieveProductModelsTask $retrieveProductModelsTask */
        $retrieveProductModelsTask = $this->taskProvider->get(RetrieveProductModelsTask::class);
        $optionsPayload = $retrieveProductModelsTask->__invoke($productModelPayload);

        foreach ($optionsPayload->getResources() as $resource) {
            if ($resource['parent'] === null) {
                continue;
            }
            $productBase = $resource;

            break;
        }

        /** @var AddOrUpdateProductModelTask $addOrUpdateProductModelsTask */
        $addOrUpdateProductModelsTask = $this->taskProvider->get(AddOrUpdateProductModelTask::class);
        $productModelPayload = $addOrUpdateProductModelsTask->__invoke($optionsPayload);

        /** @var \Synolia\SyliusAkeneoPlugin\Task\ProductModel\EnableDisableProductModelsTask $enableDisableProductModelTask */
        $enableDisableProductModelTask = $this->taskProvider->get(EnableDisableProductModelsTask::class);
        $enableDisableProductModelTask->__invoke($productModelPayload);

        /** @var Product $product */
        $product = $this->manager->getRepository(Product::class)->findOneBy(['code' => $productBase['code']]);
        $this->assertCount(1, $product->getChannels());
        $channel = $this->manager->getRepository(Channel::class)->findOneBy(['code' => 'FASHION_WEB']);
        $this->assertContains($channel, $product->getChannels());
    }
}
