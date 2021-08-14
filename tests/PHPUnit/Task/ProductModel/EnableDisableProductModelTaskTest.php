<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\ProductModel;

use Sylius\Component\Core\Model\Product;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\ProcessProductModelsTask;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\SetupProductModelTask;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\TearDownProductModelTask;

/**
 * @internal
 * @coversNothing
 */
final class EnableDisableProductModelTaskTest extends AbstractTaskTest
{
    /** @var AkeneoTaskProvider */
    private $taskProvider;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var AkeneoAttributePropertiesProvider $akeneoPropertiesProvider */
        $akeneoPropertiesProvider = $this->getContainer()->get(AkeneoAttributePropertiesProvider::class);
        $akeneoPropertiesProvider->setLoadsAllAttributesAtOnce(true);
        $this->taskProvider = $this->getContainer()->get(AkeneoTaskProvider::class);
        self::assertInstanceOf(AkeneoTaskProvider::class, $this->taskProvider);
    }

    public function testEnableDisableProductModelTask(): void
    {
        $this->createProductConfiguration();

        $productModelPayload = new ProductModelPayload($this->createClient());
        $productModelPayload->disableBatching();

        $setupProductModelsTask = $this->taskProvider->get(SetupProductModelTask::class);
        $productModelPayload = $setupProductModelsTask->__invoke($productModelPayload);

        /** @var ProcessProductModelsTask $processProductModelsTask */
        $processProductModelsTask = $this->taskProvider->get(ProcessProductModelsTask::class);
        $productModelPayload = $processProductModelsTask->__invoke($productModelPayload);

        /** @var TearDownProductModelTask $tearDownProductModelTask */
        $tearDownProductModelTask = $this->taskProvider->get(TearDownProductModelTask::class);
        $tearDownProductModelTask->__invoke($productModelPayload);

        /** @var Product $product */
        $product = $this->getContainer()->get('sylius.repository.product')->findOneBy(['code' => 'apollon_yellow']);
        $this->assertCount(1, $product->getChannels());
        $channel = $this->getContainer()->get('sylius.repository.channel')->findOneBy(['code' => 'FASHION_WEB']);
        $this->assertContains($channel, $product->getChannels());
    }
}
