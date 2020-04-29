<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\ProductModel;

use Akeneo\Pim\ApiClient\Api\ProductModelApi;
use donatj\MockWebServer\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfigurationAkeneoImageAttribute;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfigurationImageMapping;
use Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Api\ApiTestCase;

abstract class AbstractTaskTest extends ApiTestCase
{
    /** @var ApiConfiguration */
    protected $apiConfiguration;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->manager = self::$container->get('doctrine')->getManager();

        $this->initializeApiConfiguration();

        $this->manager->flush();

        $this->server->setResponseOfPath(
            '/' . sprintf(ProductModelApi::PRODUCT_MODELS_URI),
            new Response($this->getFileContent('product_models.json'), [], HttpResponse::HTTP_OK)
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
}
