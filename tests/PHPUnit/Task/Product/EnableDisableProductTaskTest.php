<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Product;

use Sylius\Component\Core\Model\Taxon;
use Sylius\Component\Core\Model\TaxonInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Provider\TaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\Product\ProcessProductsTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\SetupProductTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\TearDownProductTask;

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

        $setupProductModelsTask = $this->taskProvider->get(SetupProductTask::class);
        $productPayload = $setupProductModelsTask->__invoke($productPayload);

        /** @var ProcessProductsTask $retrieveProductsTask */
        $retrieveProductsTask = $this->taskProvider->get(ProcessProductsTask::class);
        /** @var ProductPayload $productPayload */
        $productPayload = $retrieveProductsTask->__invoke($productPayload);

        /** @var \Synolia\SyliusAkeneoPlugin\Task\Product\TearDownProductTask $tearDownProductTask */
        $tearDownProductTask = $this->taskProvider->get(TearDownProductTask::class);
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
