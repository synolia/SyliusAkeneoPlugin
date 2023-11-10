<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\ProductModel;

use Akeneo\Pim\ApiClient\Api\FamilyVariantApi;
use donatj\MockWebServer\Response;
use Sylius\Component\Core\Model\Product;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Provider\TaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\ProcessProductModelsTask;
use Synolia\SyliusAkeneoPlugin\Task\SetupTask;
use Synolia\SyliusAkeneoPlugin\Task\TearDownTask;

/**
 * @internal
 *
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
        $this->taskProvider = $this->getContainer()->get(TaskProvider::class);
        self::assertInstanceOf(TaskProvider::class, $this->taskProvider);

        $this->server->setResponseOfPath(
            '/' . sprintf(FamilyVariantApi::FAMILY_VARIANT_URI, 'clothing', 'clothing_color_size'),
            new Response($this->getFileContent('family_variant_clothing_color_size.json'), [], HttpResponse::HTTP_OK),
        );
    }

    public function testEnableDisableProductModelTask(): void
    {
        $this->createProductConfiguration();

        $productModelPayload = new ProductModelPayload($this->createClient());
        $productModelPayload->disableBatching();

        $setupProductModelsTask = $this->taskProvider->get(SetupTask::class);
        $productModelPayload = $setupProductModelsTask->__invoke($productModelPayload);

        /** @var ProcessProductModelsTask $processProductModelsTask */
        $processProductModelsTask = $this->taskProvider->get(ProcessProductModelsTask::class);
        $productModelPayload = $processProductModelsTask->__invoke($productModelPayload);

        $tearDownProductModelTask = $this->taskProvider->get(TearDownTask::class);
        $tearDownProductModelTask->__invoke($productModelPayload);

        /** @var Product $product */
        $product = $this->getContainer()->get('sylius.repository.product')->findOneBy(['code' => 'apollon_yellow']);
        $this->assertCount(1, $product->getChannels());
        $channel = $this->getContainer()->get('sylius.repository.channel')->findOneBy(['code' => 'FASHION_WEB']);
        $this->assertContains($channel, $product->getChannels());
    }
}
