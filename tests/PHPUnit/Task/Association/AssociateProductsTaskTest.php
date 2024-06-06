<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Association;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\AssociationTypeApi;
use Akeneo\Pim\ApiClient\Search\Operator;
use donatj\MockWebServer\Response;
use League\Pipeline\Pipeline;
use PHPUnit\Framework\Assert;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroup;
use Synolia\SyliusAkeneoPlugin\Factory\AssociationTypePipelineFactory;
use Synolia\SyliusAkeneoPlugin\Factory\CategoryPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Factory\ProductModelPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Payload\Association\AssociationPayload;
use Synolia\SyliusAkeneoPlugin\Payload\Association\AssociationTypePayload;
use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Provider\Data\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Provider\TaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\Association\AssociateProductsTask;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\ProcessProductModelsTask;
use Synolia\SyliusAkeneoPlugin\Task\SetupTask;
use Synolia\SyliusAkeneoPlugin\Task\TearDownTask;
use Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Product\AbstractTaskTest;

class AssociateProductsTaskTest extends AbstractTaskTest
{
    private TaskProvider $taskProvider;

    private ProductRepositoryInterface $productRepository;

    private EntityRepository $productGroupRepository;

    private AkeneoPimClientInterface $client;

    private ?ProductFiltersRules $productFiltersRules;

    protected function setUp(): void
    {
        parent::setUp();

        $this->server->setResponseOfPath(
            '/' . sprintf(AssociationTypeApi::ASSOCIATION_TYPES_URI),
            new Response($this->getFileContent('association_types.json'), [], HttpResponse::HTTP_OK),
        );

        /** @var AkeneoAttributePropertiesProvider $akeneoPropertiesProvider */
        $akeneoPropertiesProvider = $this->getContainer()->get(AkeneoAttributePropertiesProvider::class);
        $akeneoPropertiesProvider->setLoadsAllAttributesAtOnce(true);
        $this->taskProvider = $this->getContainer()->get(TaskProvider::class);
        $this->productRepository = $this->getContainer()->get('sylius.repository.product');
        $this->productGroupRepository = $this->getContainer()->get('akeneo.repository.product_group');

        $this->client = $this->createClient();

        self::assertInstanceOf(TaskProvider::class, $this->taskProvider);
    }

    public function testCreateUpdateTask(): void
    {
        $this->createProductFiltersConfiguration();
        $this->createProductConfiguration();
        $this->importCategories();
        $this->importAttributes();
        $this->importAssociationTypes();
        $this->importProductModels();

        $productModelPayload = new ProductModelPayload($this->createClient());
        $productModelPayload->setProcessAsSoonAsPossible(false);

        $setupProductModelsTask = $this->taskProvider->get(SetupTask::class);
        $productModelPayload = $setupProductModelsTask->__invoke($productModelPayload);

        /** @var ProcessProductModelsTask $processProductModelsTask */
        $processProductModelsTask = $this->taskProvider->get(ProcessProductModelsTask::class);
        $productModelPayload = $processProductModelsTask->__invoke($productModelPayload);

        $tearDownProductModelTask = $this->taskProvider->get(TearDownTask::class);
        $tearDownProductModelTask->__invoke($productModelPayload);

        $associateProductsTask = $this->taskProvider->get(AssociateProductsTask::class);
        $associateProductsTask->__invoke(new AssociationPayload($this->createClient()));

        $productGroups = $this->productGroupRepository->findAll();

        /** @var ProductGroup $productGroup */
        foreach ($productGroups as $productGroup) {
            /* imported from association_types.json */
            self::assertCount(4, $productGroup->getAssociations());
            self::assertArrayHasKey('PACK', $productGroup->getAssociations());
            self::assertArrayHasKey('UPSELL', $productGroup->getAssociations());
            self::assertArrayHasKey('X_SELL', $productGroup->getAssociations());
            self::assertArrayHasKey('SUBSTITUTION', $productGroup->getAssociations());
        }

        // apollon_yellow
        self::assertEquals('apollon_blue', $productGroups[1]->getAssociations()['SUBSTITUTION']['product_models'][0]);
        self::assertEquals('apollon_green', $productGroups[1]->getAssociations()['SUBSTITUTION']['product_models'][1]);

        /** @var Product $finalProduct */
        $finalProduct = $this->productRepository->findOneBy(['code' => 'apollon_yellow']);
        Assert::assertInstanceOf(Product::class, $finalProduct);

        $associations = $finalProduct->getAssociations();
        self::assertCount(1, $associations);
        self::assertEquals('SUBSTITUTION', $associations[0]->getType()->getCode());
        self::assertEquals('apollon_yellow', $associations[0]->getOwner()->getCode());
        self::assertCount(2, $associations[0]->getAssociatedProducts()); //apollon_blue, apollon_green
        self::assertEquals('apollon_blue', $associations[0]->getAssociatedProducts()[0]->getCode());
        self::assertEquals('apollon_green', $associations[0]->getAssociatedProducts()[1]->getCode());
    }

    private function importCategories(): void
    {
        $categoryPayload = new CategoryPayload($this->client);
        /** @var Pipeline $categoryPipeline */
        $categoryPipeline = $this->getContainer()->get(CategoryPipelineFactory::class)->create();

        $categoryPipeline->process($categoryPayload);
    }

    private function importProductModels(): void
    {
        $productModelPayload = new ProductModelPayload($this->client);
        $productModelPayload->setProcessAsSoonAsPossible(false);

        /** @var Pipeline $productModelPipeline */
        $productModelPipeline = $this->getContainer()->get(ProductModelPipelineFactory::class)->create();

        $productModelPipeline->process($productModelPayload);
    }

    private function importAssociationTypes(): void
    {
        $associationTypePayload = new AssociationTypePayload($this->client);
        $associationTypePayload->setProcessAsSoonAsPossible(false);

        /** @var Pipeline $associationTypePipeline */
        $associationTypePipeline = $this->getContainer()->get(AssociationTypePipelineFactory::class)->create();

        $associationTypePipeline->process($associationTypePayload);
    }

    private function createProductFiltersConfiguration(): void
    {
        $this->productFiltersRules = $this->manager->getRepository(ProductFiltersRules::class)->findOneBy([]);
        if (!$this->productFiltersRules instanceof ProductFiltersRules) {
            $this->productFiltersRules = new ProductFiltersRules();
            $this->manager->persist($this->productFiltersRules);
        }
        $this->productFiltersRules
            ->setMode('simple')
            ->setCompletenessType(Operator::EQUAL)
            ->setCompletenessValue(100)
            ->setChannel('ecommerce')
            ->setUpdatedAfter(new \DateTime('2020-04-04'))
            ->setUpdatedBefore(new \DateTime('2020-04-04'))
        ;

        $this->manager->flush();
    }
}
