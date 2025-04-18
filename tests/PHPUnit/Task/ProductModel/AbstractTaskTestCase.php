<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\ProductModel;

use Akeneo\Pim\ApiClient\Api\AttributeApi;
use Akeneo\Pim\ApiClient\Api\FamilyApi;
use Akeneo\Pim\ApiClient\Api\LocaleApi;
use Akeneo\Pim\ApiClient\Api\ProductModelApi;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Statement;
use donatj\MockWebServer\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfigurationAkeneoImageAttribute;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfigurationImageMapping;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Api\ApiTestCase;

abstract class AbstractTaskTestCase extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->manager->flush();

        $this->server->setResponseOfPath(
            '/' . AttributeApi::ATTRIBUTES_URI,
            new Response($this->getFileContent('attributes_options_apollon.json'), [], HttpResponse::HTTP_OK),
        );

        $this->server->setResponseOfPath(
            '/' . ProductModelApi::PRODUCT_MODELS_URI,
            new Response($this->getFileContent('product_models_apollon.json'), [], HttpResponse::HTTP_OK),
        );

        $this->server->setResponseOfPath(
            '/' . LocaleApi::LOCALES_URI,
            new Response($this->getFileContent('locales.json'), [], HttpResponse::HTTP_OK),
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(FamilyApi::FAMILY_URI, 'clothing'),
            new Response($this->getFileContent('family_clothing.json'), [], HttpResponse::HTTP_OK),
        );
    }

    protected function tearDown(): void
    {
        $this->manager->close();
        $this->manager = null;

        $this->server->stop();

        parent::tearDown();
    }

    protected function createProductConfiguration(): void
    {
        $productConfiguration = new ProductConfiguration();
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

    protected function countTotalProducts(): int
    {
        $query = $this->manager->getConnection()->prepare(sprintf(
            'SELECT count(id) FROM `%s`',
            ProductModelPayload::TEMP_AKENEO_TABLE_NAME,
        ));
        $query->executeStatement();

        return (int) current($query->fetch());
    }

    protected function prepareSelectQuery(
        int $limit = 100,
        int $offset = 0,
    ): Statement {
        $query = $this->manager->getConnection()->prepare(sprintf(
            'SELECT `values` 
             FROM `%s` 
             LIMIT :limit
             OFFSET :offset',
            ProductModelPayload::TEMP_AKENEO_TABLE_NAME,
        ));
        $query->bindValue('limit', $limit, ParameterType::INTEGER);
        $query->bindValue('offset', $offset, ParameterType::INTEGER);

        return $query;
    }
}
