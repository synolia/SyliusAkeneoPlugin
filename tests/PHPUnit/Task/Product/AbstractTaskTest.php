<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Product;

use Akeneo\Pim\ApiClient\Api\AttributeApi;
use Akeneo\Pim\ApiClient\Api\AttributeOptionApi;
use Akeneo\Pim\ApiClient\Api\CategoryApi;
use Akeneo\Pim\ApiClient\Api\FamilyApi;
use Akeneo\Pim\ApiClient\Api\FamilyVariantApi;
use Akeneo\Pim\ApiClient\Api\LocaleApi;
use Akeneo\Pim\ApiClient\Api\ProductApi;
use Akeneo\Pim\ApiClient\Api\ProductMediaFileApi;
use Akeneo\Pim\ApiClient\Api\ProductModelApi;
use Akeneo\PimEnterprise\ApiClient\Api\ReferenceEntityRecordApi;
use donatj\MockWebServer\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfigurationAkeneoImageAttribute;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfigurationImageMapping;
use Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules;
use Synolia\SyliusAkeneoPlugin\Factory\AttributePipelineFactory;
use Synolia\SyliusAkeneoPlugin\Form\Type\ProductFilterRuleAdvancedType;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Api\ApiTestCase;

abstract class AbstractTaskTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->manager = $this->getContainer()->get('doctrine')->getManager();

        $this->initializeApiConfiguration();
        $this->createProductFiltersConfiguration();

        $this->manager->flush();

        $this->server->setResponseOfPath(
            '/' . sprintf(CategoryApi::CATEGORIES_URI),
            new Response($this->getFileContent('categories_all.json'), [], HttpResponse::HTTP_OK)
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(AttributeApi::ATTRIBUTES_URI),
            new Response($this->getFileContent('attributes_options_apollon.json'), [], HttpResponse::HTTP_OK)
        );

        $this->server->setResponseOfPath(
            '/' . FamilyApi::FAMILIES_URI,
            new Response($this->getFileContent('families.json'), [], HttpResponse::HTTP_OK)
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(FamilyVariantApi::FAMILY_VARIANTS_URI, 'clothing'),
            new Response($this->getFileContent('family_clothing_variants.json'), [], HttpResponse::HTTP_OK)
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(AttributeOptionApi::ATTRIBUTE_OPTIONS_URI, 'clothing_size'),
            new Response($this->getFileContent('attribute_options_clothing_size.json'), [], HttpResponse::HTTP_OK)
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(AttributeOptionApi::ATTRIBUTE_OPTIONS_URI, 'collection'),
            new Response($this->getFileContent('attribute_options_collection.json'), [], HttpResponse::HTTP_OK)
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(AttributeOptionApi::ATTRIBUTE_OPTIONS_URI, 'color'),
            new Response($this->getFileContent('attribute_options_color.json'), [], HttpResponse::HTTP_OK)
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(AttributeOptionApi::ATTRIBUTE_OPTIONS_URI, 'size'),
            new Response($this->getFileContent('attribute_options_size.json'), [], HttpResponse::HTTP_OK)
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(AttributeOptionApi::ATTRIBUTE_OPTIONS_URI, 'maximum_print_size'),
            new Response($this->getFileContent('attribute_options_maximum_print_size.json'), [], HttpResponse::HTTP_OK)
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(AttributeOptionApi::ATTRIBUTE_OPTIONS_URI, 'multifunctional_functions'),
            new Response($this->getFileContent('attribute_options_multifunctional_functions.json'), [], HttpResponse::HTTP_OK)
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(FamilyVariantApi::FAMILY_VARIANT_URI, 'clothing', 'clothing_color_size'),
            new Response($this->getFileContent('family_variant_clothing_color_size.json'), [], HttpResponse::HTTP_OK)
        );
        $this->server->setResponseOfPath(
            '/' . sprintf(ProductModelApi::PRODUCT_MODELS_URI),
            new Response($this->getFileContent('product_models_apollon.json'), [], HttpResponse::HTTP_OK)
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(ProductApi::PRODUCTS_URI),
            new Response($this->getFileContent('products_all.json'), [], HttpResponse::HTTP_OK)
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(ProductMediaFileApi::MEDIA_FILE_DOWNLOAD_URI, '6/3/5/c/635cbfe306a1c13867fe7671c110ee3333fcba13_bag.jpg'),
            new Response($this->getFileContent('product_1111111171.jpg'), [], HttpResponse::HTTP_OK)
        );
        $this->server->setResponseOfPath(
            '/' . sprintf(LocaleApi::LOCALES_URI),
            new Response($this->getFileContent('locales.json'), [], HttpResponse::HTTP_OK)
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(FamilyApi::FAMILY_URI, 'clothing'),
            new Response($this->getFileContent('family_clothing.json'), [], HttpResponse::HTTP_OK)
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(ReferenceEntityRecordApi::REFERENCE_ENTITY_RECORDS_URI, 'coloris'),
            new Response($this->getFileContent('reference_entity_coloris_records.json'), [], HttpResponse::HTTP_OK)
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(ReferenceEntityRecordApi::REFERENCE_ENTITY_RECORDS_URI, 'couleur'),
            new Response($this->getFileContent('reference_entity_coloris_records.json'), [], HttpResponse::HTTP_OK)
        );
    }

    protected function tearDown(): void
    {
        $this->manager->close();
        $this->manager = null;

        $this->server->stop();

        parent::tearDown();
    }

    protected function createConfiguration(): void
    {
        $apiConfiguration = new ApiConfiguration();
        $apiConfiguration->setBaseUrl('');
        $apiConfiguration->setApiClientId('');
        $apiConfiguration->setApiClientSecret('');
        $apiConfiguration->setPaginationSize(100);
        $apiConfiguration->setIsEnterprise(true);
        $apiConfiguration->setUsername('');
        $apiConfiguration->setPassword('');
        $this->manager->persist($apiConfiguration);
        $this->manager->flush();
    }

    protected function createProductConfiguration(): void
    {
        $productConfiguration = $this->manager->getRepository(ProductConfiguration::class)->findOneBy([]);
        if (!$productConfiguration instanceof ProductConfiguration) {
            $productConfiguration = new ProductConfiguration();
        }

        $productConfiguration
            ->setAkeneoPriceAttribute('price')
            ->setAkeneoEnabledChannelsAttribute('enabled_channels')
        ;
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

    private function createProductFiltersConfiguration(): void
    {
        $productFilters = new ProductFiltersRules();
        $productFilters->setMode(ProductFilterRuleAdvancedType::MODE)
            ->setAdvancedFilter('')
            ->setCompletenessValue(0)
        ;
        $this->manager->persist($productFilters);
    }

    protected function importAttributes(): void
    {
        $initialPayload = new AttributePayload($this->createClient());
        $initialPayload->setProcessAsSoonAsPossible(false);

        /** @var \League\Pipeline\Pipeline $attributePipeline */
        $attributePipeline = $this->getContainer()->get(AttributePipelineFactory::class)->create();
        $attributePipeline->process($initialPayload);
    }
}
