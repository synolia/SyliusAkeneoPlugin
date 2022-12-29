<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Asset;

use Akeneo\Pim\ApiClient\Api\LocaleApi;
use donatj\MockWebServer\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules;
use Synolia\SyliusAkeneoPlugin\Form\Type\ProductFilterRuleAdvancedType;
use Synolia\SyliusAkeneoPlugin\Provider\TaskProvider;
use Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Api\ApiTestCase;

abstract class AbstractTaskTest extends ApiTestCase
{
    protected TaskProvider $taskProvider;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->manager = self::getContainer()->get('doctrine')->getManager();

        $this->initSyliusLocales();
        $this->createProductFiltersConfiguration();

        $this->manager->flush();

        $this->server->setResponseOfPath(
            '/' . sprintf(LocaleApi::LOCALES_URI),
            new Response($this->getLocales(), [], HttpResponse::HTTP_OK),
        );

        $this->taskProvider = $this->getContainer()->get(TaskProvider::class);
    }

    protected function tearDown(): void
    {
        $this->server->stop();
        $this->manager->close();
        $this->manager = null;

        parent::tearDown();
    }

    protected function getLocales(): string
    {
        return $this->getFileContent('locales.json');
    }

    private function createProductFiltersConfiguration(): void
    {
        $productFilters = $this->manager->getRepository(ProductFiltersRules::class)->findOneBy([], ['id' => 'DESC']);

        if (null === $productFilters) {
            $productFilters = new ProductFiltersRules();
            $this->manager->persist($productFilters);
        }

        $productFilters
            ->setMode(ProductFilterRuleAdvancedType::MODE)
            ->setAdvancedFilter('')
            ->setCompletenessValue(0)
            ->setChannel('ecommerce')
        ;
    }
}
