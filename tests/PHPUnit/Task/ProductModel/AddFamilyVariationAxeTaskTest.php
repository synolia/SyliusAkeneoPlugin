<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\ProductModel;

use Akeneo\Pim\ApiClient\Api\FamilyVariantApi;
use Akeneo\Pim\ApiClient\Api\ProductModelApi;
use donatj\MockWebServer\Response;
use donatj\MockWebServer\ResponseStack;
use PHPUnit\Framework\Assert;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroup;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\AddFamilyVariationAxeTask;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\AddProductGroupsTask;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\RetrieveProductModelsTask;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\SetupProductTask;

final class AddFamilyVariationAxeTaskTest extends AbstractTaskTest
{
    /** @var AkeneoTaskProvider */
    private $taskProvider;

    /** @var EntityRepository */
    private $productGroupRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskProvider = self::$container->get(AkeneoTaskProvider::class);
        $this->productGroupRepository = self::$container->get('akeneo.repository.product_group');
        $this->server->setResponseOfPath(
            '/' . sprintf(FamilyVariantApi::FAMILY_VARIANT_URI, 'clothing', 'clothing_color_size'),
            new Response($this->getFileContent('family_variant_clothing_color_size.json'), [], HttpResponse::HTTP_OK)
        );
        $this->server->setResponseOfPath(
            '/' . sprintf(ProductModelApi::PRODUCT_MODELS_URI),
            new Response($this->getFileContent('product_models_caelus.json'), [], HttpResponse::HTTP_OK)
        );

        self::assertInstanceOf(AkeneoTaskProvider::class, $this->taskProvider);
    }

    public function testAddOrUpdateProductModelTask(): void
    {
        $productModelPayload = new ProductModelPayload($this->createClient());

        $setupProductModelsTask = $this->taskProvider->get(SetupProductTask::class);
        $productModelPayload = $setupProductModelsTask->__invoke($productModelPayload);

        /** @var RetrieveProductModelsTask $retrieveProductModelsTask */
        $retrieveProductModelsTask = $this->taskProvider->get(RetrieveProductModelsTask::class);
        $productsPayload = $retrieveProductModelsTask->__invoke($productModelPayload);

        $addProductGroupsTask = $this->taskProvider->get(AddProductGroupsTask::class);
        $productGroupsPayload = $addProductGroupsTask->__invoke($productsPayload);
        /** @var ProductGroup $productGroup */
        $productGroup = $this->productGroupRepository->findOneBy(['productParent' => 'caelus']);
        Assert::assertInstanceOf(ProductGroup::class, $productGroup);

        /** @var AddFamilyVariationAxeTask $addFamilyVariationAxes */
        $addFamilyVariationAxes = $this->taskProvider->get(AddFamilyVariationAxeTask::class);
        $productModelPayload = $addFamilyVariationAxes->__invoke($productGroupsPayload);
        Assert::assertInstanceOf(PipelinePayloadInterface::class, $productModelPayload);
        Assert::assertNotEmpty($productGroup->getVariationAxes());

        $this->server->setResponseOfPath(
            '/' . sprintf(FamilyVariantApi::FAMILY_VARIANT_URI, 'clothing', 'clothing_color_size'),
            new ResponseStack(
                new Response($this->getFileContent('family_variant_clothing_color_size.json'), [], HttpResponse::HTTP_OK)
            )
        );
        $familyVariant = $productsPayload->getAkeneoPimClient()->getFamilyVariantApi()->get('clothing', 'clothing_color_size');
        foreach ($familyVariant['variant_attribute_sets'] as $key => $variantAttributeSet) {
            if (count($familyVariant['variant_attribute_sets']) !== $variantAttributeSet['level']) {
                continue;
            }
            foreach ($variantAttributeSet['axes'] as $axe) {
                assert::assertContains($axe, $productGroup->getVariationAxes());
            }
        }
    }
}
