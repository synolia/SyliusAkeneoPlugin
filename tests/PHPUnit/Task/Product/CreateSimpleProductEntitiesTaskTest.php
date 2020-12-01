<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Product;

use Akeneo\Pim\ApiClient\Api\AttributeApi;
use Akeneo\Pim\ApiClient\Api\LocaleApi;
use Akeneo\Pim\ApiClient\Api\ProductApi;
use Akeneo\PimEnterprise\ApiClient\Api\ReferenceEntityAttributeApi;
use Akeneo\PimEnterprise\ApiClient\Api\ReferenceEntityAttributeOptionApi;
use Akeneo\PimEnterprise\ApiClient\Api\ReferenceEntityRecordApi;
use donatj\MockWebServer\Response;
use Sylius\Bundle\ProductBundle\Doctrine\ORM\ProductAttributeValueRepository;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Core\Model\Taxon;
use Sylius\Component\Core\Model\TaxonInterface;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Repository\ProductAttributeRepository;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\CreateUpdateEntityTask;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\RetrieveAttributesTask;
use Synolia\SyliusAkeneoPlugin\Task\AttributeOption\CreateUpdateDeleteTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\CreateSimpleProductEntitiesTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\RetrieveProductsTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\SetupProductTask;

final class CreateSimpleProductEntitiesTaskTest extends AbstractTaskTest
{
    /** @var AkeneoTaskProvider */
    private $taskProvider;

    /** @var \Akeneo\Pim\ApiClient\AkeneoPimClientInterface */
    private $client;

    protected function setUp(): void
    {
        parent::setUp();

        $akeneoPropertiesProvider = self::$container->get(AkeneoAttributePropertiesProvider::class);
        $akeneoPropertiesProvider->setLoadsAllAttributesAtOnce(true);
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

    public function testCreateSimpleProductsTask(): void
    {
        /** @var ProductAttributeRepository $productAttributeRepository */
        $productAttributeRepository = self::$container->get(ProductAttributeRepository::class);
        /** @var ProductAttributeValueRepository $productAttributeValueRepository */
        $productAttributeValueRepository = self::$container->get('sylius.repository.product_attribute_value');

        $this->createProductConfiguration();
        $this->importCategories();
        $this->importAttributes();
        $this->importReferenceEntities();

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
        $createSimpleProductEntitiesTask->__invoke($productPayload);

        /** @var \Sylius\Component\Core\Model\ProductInterface $product */
        $product = $this->manager->getRepository(Product::class)->findOneBy(['code' => '1111111171']);
        $this->assertNotNull($product);

        //Testing product attribute translations inside models
        $product->setCurrentLocale('en_US');
        $this->assertEquals('Bag', $product->getName());

        //Testing product attribute translations
        foreach ($product->getAttributes() as $attribute) {
            if ('ean' === $attribute->getCode()) {
                $this->assertEquals('1234567890183', $attribute->getValue());
            }
        }

        //Testing image import
        $this->assertCount(1, $product->getImages());

        //Testing categories
        $categories = ['master_accessories_bags', 'print_accessories', 'supplier_zaro'];
        $this->assertCount(\count($categories), $product->getTaxons());
        foreach ($product->getTaxons() as $taxon) {
            $this->assertContains($taxon->getCode(), $categories);
        }

        //Testing simple variant
        /** @var \Sylius\Component\Core\Model\ProductVariantInterface $productVariant */
        $productVariant = $this->manager->getRepository(ProductVariant::class)->findOneBy(['code' => $product->getCode()]);
        $this->assertNotNull($productVariant);

        $this->assertEquals(self::$container->get('sylius.repository.channel')->count([]), $productVariant->getChannelPricings()->count());
        foreach ($productVariant->getChannelPricings() as $channelPricing) {
            $this->assertEquals(89900, $channelPricing->getPrice());
            $this->assertEquals(89900, $channelPricing->getOriginalPrice());
        }

        /** @var \Sylius\Component\Product\Model\ProductAttributeValueInterface $referenceEntityAttribute */
        $referenceEntityAttribute = $productAttributeRepository->findOneBy(['code' => 'test_entite_couleur']);

        /** @var \Sylius\Component\Product\Model\ProductAttributeValueInterface $referenceEntityAttributeValue */
        $referenceEntityAttributeValue = $productAttributeValueRepository->findOneBy([
            'subject' => $product,
            'attribute' => $referenceEntityAttribute,
            'localeCode' => 'fr_FR',
        ]);
        $this->assertNotNull($referenceEntityAttributeValue);

        $minifiedJson = \preg_replace(
            '/\s(?=([^"]*"[^"]*")*[^"]*$)/',
            '',
            '{"code":"noir","values":{"label":"BLANC","image":"e\/b\/4\/d\/eb4d25582151b684acd7f18f68b1db5314786233_blanc.png","filtre_couleur_1":"noir"}}'
        );

        $this->assertSame(
            $minifiedJson,
            $referenceEntityAttributeValue->getValue()
        );
    }

    public function createSimpleProductsWithMultiSelectCheckboxDataProvider(): \Generator
    {
        yield [
            '11834327',
            CreateUpdateDeleteTask::AKENEO_PREFIX . 'legal_216_x_356_mm_',
            [
                CreateUpdateDeleteTask::AKENEO_PREFIX . 'copy',
                CreateUpdateDeleteTask::AKENEO_PREFIX . 'n',
                CreateUpdateDeleteTask::AKENEO_PREFIX . 'scan',
            ],
            true,
        ];

        yield [
            '123456789',
            CreateUpdateDeleteTask::AKENEO_PREFIX . 'legal_216_x_356_mm_',
            [CreateUpdateDeleteTask::AKENEO_PREFIX . 'copy'],
            false,
        ];
    }

    /**
     * @dataProvider createSimpleProductsWithMultiSelectCheckboxDataProvider
     */
    public function testCreateSimpleProductsWithMultiSelectCheckboxTask(
        string $productId,
        string $selectValue,
        array $multiSelectValue,
        bool $checkboxValue
    ): void {
        $this->initializeProductWithMultiSelectAndCheckbox();

        $productPayload = new ProductPayload($this->client);

        $setupProductModelsTask = $this->taskProvider->get(SetupProductTask::class);
        $productPayload = $setupProductModelsTask->__invoke($productPayload);

        /** @var RetrieveProductsTask $retrieveProductsTask */
        $retrieveProductsTask = $this->taskProvider->get(RetrieveProductsTask::class);
        /** @var ProductPayload $productPayload */
        $productPayload = $retrieveProductsTask->__invoke($productPayload);

        $this->assertSame(2, $this->countTotalProducts(true));

        /** @var CreateSimpleProductEntitiesTask $createSimpleProductEntitiesTask */
        $createSimpleProductEntitiesTask = $this->taskProvider->get(CreateSimpleProductEntitiesTask::class);
        $createSimpleProductEntitiesTask->__invoke($productPayload);

        /** @var \Sylius\Component\Core\Model\ProductInterface $product */
        $product = $this->manager->getRepository(Product::class)->findOneBy(['code' => $productId]);
        $this->assertNotNull($product);

        $this->assertNotEmpty($product->getAttributes());
        foreach ($product->getAttributes() as $attribute) {
            if ($attribute->getCode() === 'maximum_print_size') {
                $this->assertCount(1, $attribute->getValue());
                $this->assertEquals($selectValue, $attribute->getValue()[0]);
            }
            if ($attribute->getCode() === 'multifunctional_functions') {
                $this->assertGreaterThanOrEqual(1, $attribute->getValue());
                $this->assertEquals($multiSelectValue, $attribute->getValue());
            }
            if ($attribute->getCode() === 'color_scanning') {
                $this->assertEquals($checkboxValue, $attribute->getValue());
            }
        }
    }

    private function initializeProductWithMultiSelectAndCheckbox(): void
    {
        $this->server->setResponseOfPath(
            '/' . sprintf(ProductApi::PRODUCTS_URI),
            new Response($this->getFileContent('products_attributes_value_test.json'), [], HttpResponse::HTTP_OK)
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(AttributeApi::ATTRIBUTES_URI),
            new Response($this->getFileContent('attributes_for_products_attributes_value_test.json'), [], HttpResponse::HTTP_OK)
        );

        $this->createProductConfiguration();
        $this->importCategories();
        $this->importAttributes();
        $this->importReferenceEntities();
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
            $category = $this->manager->getRepository(Taxon::class)->findOneBy(['code' => $categoryCode]);

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

    private function importReferenceEntities()
    {
        $this->server->setResponseOfPath(
            '/' . sprintf(LocaleApi::LOCALES_URI),
            new Response($this->getFileContent('locales.json'), [], HttpResponse::HTTP_OK)
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(ReferenceEntityRecordApi::REFERENCE_ENTITY_RECORDS_URI, 'couleur'),
            new Response($this->getFileContent('entity_couleur_records.json'), [], HttpResponse::HTTP_OK)
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(ReferenceEntityAttributeApi::REFERENCE_ENTITY_ATTRIBUTES_URI, 'couleur'),
            new Response($this->getFileContent('entity_couleur_attributes.json'), [], HttpResponse::HTTP_OK)
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(ReferenceEntityAttributeOptionApi::REFERENCE_ENTITY_ATTRIBUTE_OPTIONS_URI, 'couleur', 'filtre_couleur_1'),
            new Response($this->getFileContent('entity_couleur_filtre_couleur_1_options.json'), [], HttpResponse::HTTP_OK)
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(ReferenceEntityRecordApi::REFERENCE_ENTITY_RECORD_URI, 'couleur', 'noir'),
            new Response($this->getFileContent('reference_entity_couleur_record_noir.json'), [], HttpResponse::HTTP_OK)
        );
    }
}
