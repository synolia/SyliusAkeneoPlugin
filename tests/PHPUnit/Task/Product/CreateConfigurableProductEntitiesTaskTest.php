<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Product;

use Akeneo\Pim\ApiClient\Search\Operator;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Product\Model\ProductOptionValueTranslation;
use Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules;
use Synolia\SyliusAkeneoPlugin\Factory\AttributeOptionPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Factory\AttributePipelineFactory;
use Synolia\SyliusAkeneoPlugin\Factory\CategoryPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Factory\ProductModelPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Filter\ProductFilter;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\Product\CreateConfigurableProductEntitiesTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\RetrieveProductsTask;

final class CreateConfigurableProductEntitiesTaskTest extends AbstractTaskTest
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

    public function testCreateConfigurableProductsTask(): void
    {
        $this->createConfiguration();
        $this->createProductFiltersConfiguration();
        $this->importCategories();
        $attributePayload = $this->importAttributes();
        $this->importAttributeOptions($attributePayload);
        $this->importProductModels();
        $this->createProductConfiguration();

        $this->manager->flush();

        $productPayload = new ProductPayload($this->client);

        /** @var RetrieveProductsTask $retrieveProductsTask */
        $retrieveProductsTask = $this->taskProvider->get(RetrieveProductsTask::class);
        /** @var ProductPayload $productPayload */
        $productPayload = $retrieveProductsTask->__invoke($productPayload);

        $this->assertCount(13, $productPayload->getConfigurableProductPayload()->getProducts());

        /** @var CreateConfigurableProductEntitiesTask $createConfigurableProductEntitiesTask */
        $createConfigurableProductEntitiesTask = $this->taskProvider->get(CreateConfigurableProductEntitiesTask::class);
        $createConfigurableProductEntitiesTask->__invoke($productPayload);

        $productsToTest = [
            [
                'code' => '1111111130',
                'name' => 'Long gray suit jacket and matching pants unstructured Apollon yellow',
                'attributes' => [
                    'size' => 'xs',
                ],
                'price' => 89900,
            ],
            [
                'code' => '1111111131',
                'name' => 'Long gray suit jacket and matching pants unstructured Apollon yellow',
                'attributes' => [
                    'size' => 'xl',
                ],
                'price' => 89000,
            ],
            [
                'code' => '1111111119',
                'name' => 'Long gray suit jacket and matching pants unstructured Apollon blue',
                'attributes' => [
                    'size' => 'xxl',
                ],
                'price' => 76543,
            ],
        ];

        foreach ($productsToTest as $productToTest) {
            /** @var \Sylius\Component\Core\Model\ProductVariantInterface $productVariant */
            $productVariant = $this->manager->getRepository(ProductVariant::class)->findOneBy(['code' => $productToTest['code']]);
            $this->assertNotNull($productVariant);

            //Testing product attribute translations inside models
            $productVariant->setCurrentLocale('en_US');
            $this->assertEquals($productToTest['name'], $productVariant->getProduct()->getName());

            //Testing product attribute translations
            foreach ($productVariant->getProduct()->getAttributes() as $attribute) {
                if ('size' === $attribute->getCode()) {
                    $this->assertEquals($productToTest['attributes']['size'], $attribute->getValue());
                }
            }

            //Testing product attribute translations
            foreach ($productVariant->getOptionValues() as $optionValue) {
                if (!'size_' . $productToTest['attributes']['size'] === $optionValue->getCode()) {
                    continue;
                }
                $productOptionValueTranslation = $this->manager->getRepository(ProductOptionValueTranslation::class)->findOneBy([
                    'translatable' => $optionValue,
                    'locale' => 'en_US',
                ]);
                $this->assertEquals(
                    \strtoupper($productToTest['attributes']['size']),
                    $productOptionValueTranslation->getValue()
                );
            }

            //Testing image import
            $this->assertCount(1, $productVariant->getImages());

            $this->assertEquals(1, $productVariant->getChannelPricings()->count());
            foreach ($productVariant->getChannelPricings() as $channelPricing) {
                $this->assertEquals($productToTest['price'], $channelPricing->getPrice());
                $this->assertEquals($productToTest['price'], $channelPricing->getOriginalPrice());
            }
        }
    }

    private function importAttributes(): PipelinePayloadInterface
    {
        /** @var \Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload $attributePayload */
        $attributePayload = new AttributePayload($this->client);
        /** @var \League\Pipeline\Pipeline $attributePipeline */
        $attributePipeline = self::$container->get(AttributePipelineFactory::class)->create();

        return $attributePipeline->process($attributePayload);
    }

    private function importAttributeOptions(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        /** @var \League\Pipeline\Pipeline $optionPipeline */
        $optionPipeline = self::$container->get(AttributeOptionPipelineFactory::class)->create();

        return $optionPipeline->process($payload);
    }

    private function importCategories(): void
    {
        /** @var \Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload $categoryPayload */
        $categoryPayload = new CategoryPayload($this->client);
        /** @var \League\Pipeline\Pipeline $categoryPipeline */
        $categoryPipeline = self::$container->get(CategoryPipelineFactory::class)->create();

        $categoryPipeline->process($categoryPayload);
    }

    private function importProductModels(): void
    {
        /** @var \Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload $productModelPayload */
        $productModelPayload = new ProductModelPayload($this->client);
        /** @var \League\Pipeline\Pipeline $productModelPipeline */
        $productModelPipeline = self::$container->get(ProductModelPipelineFactory::class)->create();

        $productModelPipeline->process($productModelPayload);
    }

    private function createProductFiltersConfiguration()
    {
        $this->productFilter = self::$container->get(ProductFilter::class);

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
            ->addFamily('shoes')
            ->addLocale('en_US')
            ->setUpdatedAfter(new \DateTime('2020-04-04'))
            ->setUpdatedBefore(new \DateTime('2020-04-04'))
        ;

        $this->manager->flush();
    }
}
