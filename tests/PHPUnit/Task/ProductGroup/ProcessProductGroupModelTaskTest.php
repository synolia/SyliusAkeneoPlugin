<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\ProductGroup;

use Akeneo\Pim\ApiClient\Api\FamilyVariantApi;
use donatj\MockWebServer\Response;
use PHPUnit\Framework\Assert;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroup;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Provider\Data\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Provider\TaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\ProductGroup\ProcessProductGroupModelTask;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\ProcessProductModelsTask;
use Synolia\SyliusAkeneoPlugin\Task\SetupTask;
use Synolia\SyliusAkeneoPlugin\Task\TearDownTask;
use Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\ProductModel\AbstractTaskTest;

class ProcessProductGroupModelTaskTest extends AbstractTaskTest
{
    private TaskProvider $taskProvider;

    private ProductRepositoryInterface $productRepository;

    private EntityRepository $productGroupRepository;

    private ProcessProductGroupModelTask $processProductGroupModelTask;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var AkeneoAttributePropertiesProvider $akeneoPropertiesProvider */
        $akeneoPropertiesProvider = $this->getContainer()->get(AkeneoAttributePropertiesProvider::class);
        $akeneoPropertiesProvider->setLoadsAllAttributesAtOnce(true);
        $this->taskProvider = $this->getContainer()->get(TaskProvider::class);
        $this->productRepository = $this->getContainer()->get('sylius.repository.product');
        $this->productGroupRepository = $this->getContainer()->get('akeneo.repository.product_group');
        $this->processProductGroupModelTask = $this->getContainer()->get(ProcessProductGroupModelTask::class);
        self::assertInstanceOf(TaskProvider::class, $this->taskProvider);

        $this->server->setResponseOfPath(
            '/' . sprintf(FamilyVariantApi::FAMILY_VARIANT_URI, 'clothing', 'clothing_color_size'),
            new Response($this->getFileContent('family_variant_clothing_color_size.json'), [], HttpResponse::HTTP_OK),
        );
    }

    public function testCreateProductGroupAssociations(): void
    {
        $this->prepareConfiguration();

        $productModelPayload = new ProductModelPayload($this->createClient());
        $productModelPayload->setProcessAsSoonAsPossible(false);

        $setupProductModelsTask = $this->taskProvider->get(SetupTask::class);
        $productModelPayload = $setupProductModelsTask->__invoke($productModelPayload);

        /** @var ProcessProductModelsTask $processProductModelsTask */
        $processProductModelsTask = $this->taskProvider->get(ProcessProductModelsTask::class);
        $productModelPayload = $processProductModelsTask->__invoke($productModelPayload);

        $tearDownProductModelTask = $this->taskProvider->get(TearDownTask::class);
        $tearDownProductModelTask->__invoke($productModelPayload);

        /** @var Product $productFinal */
        $productFinal = $this->productRepository->findOneBy(['code' => 'apollon_yellow']);
        Assert::assertInstanceOf(Product::class, $productFinal);

        $productGroup = $this->productGroupRepository->findOneBy(['model' => 'apollon']);
        Assert::assertInstanceOf(ProductGroup::class, $productGroup);

        Assert::assertGreaterThan(0, $productGroup->getProducts()->count());
        $productGroup->getProducts()->clear();
        Assert::assertEquals(0, $productGroup->getProducts()->count());

        $this->processProductGroupModelTask->__invoke($productModelPayload);
        Assert::assertGreaterThan(0, $productGroup->getProducts()->count());
        Assert::assertEquals('apollon_yellow', $productGroup->getProducts()->first()->getCode());
    }

    private function prepareConfiguration(): void
    {
        $productConfiguration = new ProductConfiguration();
        $this->manager->persist($productConfiguration);
        $this->manager->flush();
    }
}
