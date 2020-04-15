<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Product;

use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Product\Model\ProductOptionValueTranslation;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfigurationAkeneoImageAttribute;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfigurationImageMapping;
use Synolia\SyliusAkeneoPlugin\Factory\AttributeOptionPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Factory\AttributePipelineFactory;
use Synolia\SyliusAkeneoPlugin\Factory\CategoryPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Factory\ProductModelPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;
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
        $this->importCategories();
        $attributePayload = $this->importAttributes();
        $this->importAttributeOptions($attributePayload);
        $this->importProductModels();
        $this->prepareProductConfiguration();

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

    private function prepareProductConfiguration(): void
    {
        $productConfiguration = new ProductConfiguration();
        $productConfiguration->setAkeneoPriceAttribute('price');
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

    private function importProductModels(): void
    {
        /** @var \Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload $productModelPayload */
        $productModelPayload = new ProductModelPayload($this->client);
        /** @var \League\Pipeline\Pipeline $productModelPipeline */
        $productModelPipeline = self::$container->get(ProductModelPipelineFactory::class)->create();

        $productModelPipeline->process($productModelPayload);
    }
}
