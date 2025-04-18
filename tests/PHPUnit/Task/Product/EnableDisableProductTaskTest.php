<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Product;

use Sylius\Component\Core\Model\Taxon;
use Sylius\Component\Core\Model\TaxonInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Provider\TaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\Product\ProcessProductsTask;
use Synolia\SyliusAkeneoPlugin\Task\SetupTask;
use Synolia\SyliusAkeneoPlugin\Task\TearDownTask;

/**
 * @internal
 *
 * @coversNothing
 */
final class EnableDisableProductTaskTest extends AbstractTaskTestCase
{
    private TaskProvider $taskProvider;

    private \Akeneo\Pim\ApiClient\AkeneoPimClientInterface $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskProvider = $this->getContainer()->get(TaskProvider::class);
        $this->client = $this->createClient();
        self::assertInstanceOf(TaskProvider::class, $this->taskProvider);
    }

    public function testEnableDisableProduct(): void
    {
        $this->createProductConfiguration();
        $this->importCategories();
        $this->importAttributes();

        $productPayload = new ProductPayload($this->client);

        $setupProductModelsTask = $this->taskProvider->get(SetupTask::class);
        $productPayload = $setupProductModelsTask->__invoke($productPayload);

        /** @var ProcessProductsTask $retrieveProductsTask */
        $retrieveProductsTask = $this->taskProvider->get(ProcessProductsTask::class);
        /** @var ProductPayload $productPayload */
        $productPayload = $retrieveProductsTask->__invoke($productPayload);

        $tearDownProductTask = $this->taskProvider->get(TearDownTask::class);
        $tearDownProductTask->__invoke($productPayload);

        $this->manager->flush();

        /** @var \Sylius\Component\Core\Model\ProductInterface $product */
        $product = $this->getContainer()->get('sylius.repository.product')->findOneBy(['code' => '1111111171']);
        $this->assertNotNull($product);

        $this->assertCount(1, $product->getChannels());
        $channel = $this->getContainer()->get('sylius.repository.channel')->findOneBy(['code' => 'FASHION_WEB']);
        $this->assertContains($channel, $product->getChannels());
    }

    private function importCategories(): void
    {
        $categories = ['master_accessories_bags', 'print_accessories', 'supplier_zaro'];

        foreach ($categories as $categoryCode) {
            $category = $this->getContainer()->get('sylius.repository.taxon')->findOneBy(['code' => $categoryCode]);

            if (!$category instanceof TaxonInterface) {
                /** @var Taxon $category */
                $category = $this->getContainer()->get('sylius.factory.taxon')->createNew();
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
