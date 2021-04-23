<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Product;

use Sylius\Component\Core\Model\Taxon;
use Sylius\Component\Core\Model\TaxonInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\CreateUpdateEntityTask;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\RetrieveAttributesTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\CreateSimpleProductEntitiesTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\EnableDisableProductsTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\RetrieveProductsTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\SetupProductTask;

/**
 * @internal
 * @coversNothing
 */
final class EnableDisableProductTaskTest extends AbstractTaskTest
{
    /** @var AkeneoTaskProvider */
    private $taskProvider;

    /** @var \Akeneo\Pim\ApiClient\AkeneoPimClientInterface */
    private $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskProvider = self::$container->get(AkeneoTaskProvider::class);
        $this->client = $this->createClient();
        self::assertInstanceOf(AkeneoTaskProvider::class, $this->taskProvider);
    }

    public function testRetrieveProductsTask(): void
    {
        $productPayload = new ProductPayload($this->client);

        /** @var RetrieveProductsTask $retrieveProductsTask */
        $retrieveProductsTask = $this->taskProvider->get(RetrieveProductsTask::class);
        $productPayload = $retrieveProductsTask->__invoke($productPayload);
        $this->assertInstanceOf(ProductPayload::class, $productPayload);
    }

    public function testEnableDisableProduct(): void
    {
        $this->createProductConfiguration();
        $this->importCategories();
        $this->importAttributes();

        $productPayload = new ProductPayload($this->client);

        $setupProductModelsTask = $this->taskProvider->get(SetupProductTask::class);
        $productPayload = $setupProductModelsTask->__invoke($productPayload);

        /** @var RetrieveProductsTask $retrieveProductsTask */
        $retrieveProductsTask = $this->taskProvider->get(RetrieveProductsTask::class);
        /** @var ProductPayload $productPayload */
        $productPayload = $retrieveProductsTask->__invoke($productPayload);

        $this->assertSame(1, $this->countTotalProducts(true));

        /** @var CreateSimpleProductEntitiesTask $createSimpleProductEntitiesTask */
        $createSimpleProductEntitiesTask = $this->taskProvider->get(CreateSimpleProductEntitiesTask::class);
        $simpleProductCreationPayload = $createSimpleProductEntitiesTask->__invoke($productPayload);

        $this->manager->flush();

        /** @var EnableDisableProductsTask $enableDisableProductTask */
        $enableDisableProductTask = $this->taskProvider->get(EnableDisableProductsTask::class);
        $enableDisableProductTask->__invoke($simpleProductCreationPayload);

        /** @var \Sylius\Component\Core\Model\ProductInterface $product */
        $product = self::$container->get('sylius.repository.product')->findOneBy(['code' => '1111111171']);

        $this->assertCount(1, $product->getChannels());
        $channel = self::$container->get('sylius.repository.channel')->findOneBy(['code' => 'FASHION_WEB']);
        $this->assertContains($channel, $product->getChannels());
    }

    private function importAttributes(): void
    {
        $initialPayload = new AttributePayload($this->client);
        /** @var RetrieveAttributesTask $retrieveTask */
        $retrieveTask = $this->taskProvider->get(RetrieveAttributesTask::class);
        $payload = $retrieveTask->__invoke($initialPayload);

        /** @var CreateUpdateEntityTask $task */
        $task = $this->taskProvider->get(CreateUpdateEntityTask::class);
        $task->__invoke($payload);
    }

    private function importCategories(): void
    {
        $categories = ['master_accessories_bags', 'print_accessories', 'supplier_zaro'];

        foreach ($categories as $categoryCode) {
            $category = self::$container->get('sylius.repository.taxon')->findOneBy(['code' => $categoryCode]);

            if (!$category instanceof TaxonInterface) {
                /** @var Taxon $category */
                $category = self::$container->get('sylius.factory.taxon')->createNew();
                $this->manager->persist($category);
            }
            $category->setCurrentLocale('en_US');
            $category->setFallbackLocale('en_US');
            $category->setCode($categoryCode);
            $category->setSlug($categoryCode);
            $category->setName($categoryCode);
        }
        $this->manager->flush();
    }
}
