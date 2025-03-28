<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\ProductModel;

use Akeneo\Pim\ApiClient\Api\FamilyVariantApi;
use donatj\MockWebServer\Response;
use PHPUnit\Framework\Assert;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\ProductRepository;
use Sylius\Component\Core\Model\Product;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfigurationAkeneoImageAttribute;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfigurationImageMapping;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroup;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Provider\Data\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Provider\TaskProvider;
use Synolia\SyliusAkeneoPlugin\Repository\ProductGroupRepository;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\ProcessProductModelsTask;
use Synolia\SyliusAkeneoPlugin\Task\SetupTask;
use Synolia\SyliusAkeneoPlugin\Task\TearDownTask;

/**
 * @internal
 *
 * @coversNothing
 */
final class AddOrUpdateProductModelTaskTest extends AbstractTaskTestCase
{
    private TaskProvider $taskProvider;

    private ?ProductRepository $productRepository = null;

    private ?ProductGroupRepository $productGroupRepository = null;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var AkeneoAttributePropertiesProvider $akeneoPropertiesProvider */
        $akeneoPropertiesProvider = $this->getContainer()->get(AkeneoAttributePropertiesProvider::class);
        $akeneoPropertiesProvider->setLoadsAllAttributesAtOnce(true);
        $this->taskProvider = $this->getContainer()->get(TaskProvider::class);
        $this->productRepository = $this->getContainer()->get('sylius.repository.product');
        $this->productGroupRepository = $this->getContainer()->get('akeneo.repository.product_group');
        self::assertInstanceOf(TaskProvider::class, $this->taskProvider);

        $this->server->setResponseOfPath(
            '/' . sprintf(FamilyVariantApi::FAMILY_VARIANT_URI, 'clothing', 'clothing_color_size'),
            new Response($this->getFileContent('family_variant_clothing_color_size.json'), [], HttpResponse::HTTP_OK),
        );
    }

    public function testCreateUpdateTask(): void
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
        $this->assertGreaterThan(0, $productFinal->getImages()->count());
        foreach ($productFinal->getImages() as $image) {
            $this->assertFileExists(self::$kernel->getProjectDir() . '/public/media/image/' . $image->getPath());
        }

        $productGroup = $this->productGroupRepository->findOneBy(['model' => 'apollon']);
        Assert::assertInstanceOf(ProductGroup::class, $productGroup);
    }

    private function prepareConfiguration(): void
    {
        $productConfiguration = new ProductConfiguration();
        $this->manager->persist($productConfiguration);

        $imageMapping = new ProductConfigurationImageMapping();
        $imageMapping->setAkeneoAttribute('picture');
        $imageMapping->setSyliusAttribute('main');
        $imageMapping->setProductConfiguration($productConfiguration);
        $this->manager->persist($imageMapping);
        $productConfiguration->addProductImagesMapping($imageMapping);

        $imageAttributes = ['picture', 'image'];

        foreach ($imageAttributes as $imageAttribute) {
            $akeneoImageAttribute = new ProductConfigurationAkeneoImageAttribute();
            $akeneoImageAttribute->setAkeneoAttributes($imageAttribute);
            $akeneoImageAttribute->setProductConfiguration($productConfiguration);
            $this->manager->persist($akeneoImageAttribute);
            $productConfiguration->addAkeneoImageAttribute($akeneoImageAttribute);
        }

        $this->manager->flush();
    }
}
