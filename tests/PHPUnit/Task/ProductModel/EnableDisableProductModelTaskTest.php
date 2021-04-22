<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\ProductModel;

use Sylius\Component\Core\Model\Product;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\AddOrUpdateProductModelTask;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\EnableDisableProductModelsTask;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\RetrieveProductModelsTask;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\SetupProductTask;

final class EnableDisableProductModelTaskTest extends AbstractTaskTest
{
    /** @var AkeneoTaskProvider */
    private $taskProvider;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var AkeneoAttributePropertiesProvider $akeneoPropertiesProvider */
        $akeneoPropertiesProvider = self::$container->get(AkeneoAttributePropertiesProvider::class);
        $akeneoPropertiesProvider->setLoadsAllAttributesAtOnce(true);
        $this->taskProvider = self::$container->get(AkeneoTaskProvider::class);
        self::assertInstanceOf(AkeneoTaskProvider::class, $this->taskProvider);
    }

    public function testEnableDisableProductModelTask(): void
    {
        $this->createProductConfiguration();

        $productModelPayload = new ProductModelPayload($this->createClient());

        $setupProductModelsTask = $this->taskProvider->get(SetupProductTask::class);
        $productModelPayload = $setupProductModelsTask->__invoke($productModelPayload);

        /** @var RetrieveProductModelsTask $retrieveProductModelsTask */
        $retrieveProductModelsTask = $this->taskProvider->get(RetrieveProductModelsTask::class);
        $optionsPayload = $retrieveProductModelsTask->__invoke($productModelPayload);

        $query = $this->prepareSelectQuery(ProductModelPayload::SELECT_PAGINATION_SIZE, 0);
        $query->execute();
        $processedCount = 0;

        while ($results = $query->fetchAll()) {
            foreach ($results as $result) {
                $resource = \json_decode($result['values'], true);

                if ($resource['parent'] === null) {
                    continue;
                }
                $productBase = $resource;

                break;
            }

            $processedCount += \count($results);
            $query = $this->prepareSelectQuery(ProductModelPayload::SELECT_PAGINATION_SIZE, $processedCount);
            $query->execute();
        }

        /** @var AddOrUpdateProductModelTask $addOrUpdateProductModelsTask */
        $addOrUpdateProductModelsTask = $this->taskProvider->get(AddOrUpdateProductModelTask::class);
        $productModelPayload = $addOrUpdateProductModelsTask->__invoke($optionsPayload);

        /** @var \Synolia\SyliusAkeneoPlugin\Task\ProductModel\EnableDisableProductModelsTask $enableDisableProductModelTask */
        $enableDisableProductModelTask = $this->taskProvider->get(EnableDisableProductModelsTask::class);
        $enableDisableProductModelTask->__invoke($productModelPayload);

        /** @var Product $product */
        $product = self::$container->get('sylius.repository.product')->findOneBy(['code' => $productBase['code']]);
        $this->assertCount(1, $product->getChannels());
        $channel = self::$container->get('sylius.repository.channel')->findOneBy(['code' => 'FASHION_WEB']);
        $this->assertContains($channel, $product->getChannels());
    }
}
