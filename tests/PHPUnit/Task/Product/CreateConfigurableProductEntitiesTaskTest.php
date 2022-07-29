<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Product;

use Akeneo\Pim\ApiClient\Api\LocaleApi;
use Akeneo\Pim\ApiClient\Search\Operator;
use Akeneo\PimEnterprise\ApiClient\Api\ReferenceEntityAttributeApi;
use Akeneo\PimEnterprise\ApiClient\Api\ReferenceEntityAttributeOptionApi;
use Akeneo\PimEnterprise\ApiClient\Api\ReferenceEntityRecordApi;
use donatj\MockWebServer\Response;
use League\Pipeline\Pipeline;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules;
use Synolia\SyliusAkeneoPlugin\Factory\CategoryPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Factory\FamilyPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Factory\ProductModelPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Filter\ProductFilter;
use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;
use Synolia\SyliusAkeneoPlugin\Payload\Family\FamilyPayload;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Provider\TaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\Product\ProcessProductsTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\SetupProductTask;
use Synolia\SyliusAkeneoPlugin\Task\Product\TearDownProductTask;

/**
 * @internal
 * @coversNothing
 */
final class CreateConfigurableProductEntitiesTaskTest extends AbstractTaskTest
{
    /** @var AkeneoTaskProvider */
    private $taskProvider;

    /** @var \Akeneo\Pim\ApiClient\AkeneoPimClientInterface */
    private $client;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var AkeneoAttributePropertiesProvider $akeneoPropertiesProvider */
        $akeneoPropertiesProvider = $this->getContainer()->get(AkeneoAttributePropertiesProvider::class);
        $akeneoPropertiesProvider->setLoadsAllAttributesAtOnce(true);
        $this->taskProvider = $this->getContainer()->get(TaskProvider::class);
        $this->client = $this->createClient();
        self::assertInstanceOf(TaskProvider::class, $this->taskProvider);
    }

    public function testCreateConfigurableProductsTask(): void
    {
        $this->createProductFiltersConfiguration();
        $this->createProductConfiguration();
        $this->importCategories();
        $this->importAttributes();
        $this->importReferenceEntities();
        $this->importFamilies();
        $this->importProductModels();

        $this->manager->flush();

        $productPayload = new ProductPayload($this->client);
        $productPayload->setProcessAsSoonAsPossible(false);

        $setupProductModelsTask = $this->taskProvider->get(SetupProductTask::class);
        $productPayload = $setupProductModelsTask->__invoke($productPayload);

        /** @var ProcessProductsTask $processProductsTask */
        $processProductsTask = $this->taskProvider->get(ProcessProductsTask::class);
        /** @var ProductPayload $productPayload */
        $productPayload = $processProductsTask->__invoke($productPayload);

        /** @var TearDownProductTask $tearDownProductTask */
        $tearDownProductTask = $this->taskProvider->get(TearDownProductTask::class);
        $tearDownProductTask->__invoke($productPayload);

        $productsToTest = [
            [
                'code' => '1111111130',
                'name' => 'Long gray suit jacket and matching pants unstructured Apollon yellow',
                'attributes' => [
                    'size' => 'XS',
                ],
                'price' => 89900,
            ],
            [
                'code' => '1111111131',
                'name' => 'Long gray suit jacket and matching pants unstructured Apollon yellow',
                'attributes' => [
                    'size' => 'XL',
                ],
                'price' => 89000,
            ],
            [
                'code' => '1111111119',
                'name' => 'Long gray suit jacket and matching pants unstructured Apollon blue',
                'attributes' => [
                    'size' => 'XXL',
                ],
                'price' => 76543,
            ],
        ];

        $productVariantRepository = $this->getContainer()->get('sylius.repository.product_variant');
        $productOptionValueTranslationRepository = $this->getContainer()->get('sylius.repository.product_option_value_translation');

        foreach ($productsToTest as $productToTest) {
            /** @var \Sylius\Component\Core\Model\ProductVariantInterface $productVariant */
            $productVariant = $productVariantRepository->findOneBy(['code' => $productToTest['code']]);
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
                $productOptionValueTranslation = $productOptionValueTranslationRepository->findOneBy([
                    'translatable' => $optionValue,
                    'locale' => 'en_US',
                ]);
                $this->assertEquals(
                    $productToTest['attributes']['size'],
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

    private function importCategories(): void
    {
        $categoryPayload = new CategoryPayload($this->client);
        /** @var \League\Pipeline\Pipeline $categoryPipeline */
        $categoryPipeline = $this->getContainer()->get(CategoryPipelineFactory::class)->create();

        $categoryPipeline->process($categoryPayload);
    }

    private function importProductModels(): void
    {
        $productModelPayload = new ProductModelPayload($this->client);
        $productModelPayload->setProcessAsSoonAsPossible(false);

        /** @var \League\Pipeline\Pipeline $productModelPipeline */
        $productModelPipeline = $this->getContainer()->get(ProductModelPipelineFactory::class)->create();

        $productModelPipeline->process($productModelPayload);
    }

    private function importFamilies(): void
    {
        /** @var Pipeline $familyPipeline */
        $familyPipeline = $this->getContainer()->get(FamilyPipelineFactory::class)->create();

        $familyPayload = new FamilyPayload($this->client);
        $familyPayload->setProcessAsSoonAsPossible(false);
        $familyPipeline->process($familyPayload);
    }

    private function createProductFiltersConfiguration(): void
    {
        $this->productFilter = $this->getContainer()->get(ProductFilter::class);

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
            ->addExcludeFamily('shoes')
            ->addLocale('en_US')
            ->setUpdatedAfter(new \DateTime('2020-04-04'))
            ->setUpdatedBefore(new \DateTime('2020-04-04'))
        ;

        $this->manager->flush();
    }

    private function importReferenceEntities(): void
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
