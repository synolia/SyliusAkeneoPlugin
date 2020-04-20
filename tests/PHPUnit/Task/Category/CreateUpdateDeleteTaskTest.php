<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Category;

use Akeneo\Pim\ApiClient\Api\CategoryApi;
use donatj\MockWebServer\Response;
use donatj\MockWebServer\ResponseStack;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoCategoryResourcesException;
use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;
use Synolia\SyliusAkeneoPlugin\Task\Category\CreateUpdateEntityTask;
use Synolia\SyliusAkeneoPlugin\Task\Category\RetrieveCategoriesTask;

final class CreateUpdateDeleteTaskTest extends AbstractTaskTest
{
    /** @var \Synolia\SyliusAkeneoPlugin\Entity\CategoryConfiguration */
    private $categoryConfiguration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->server->setResponseOfPath(
            '/' . sprintf(CategoryApi::CATEGORIES_URI),
            new ResponseStack(
                new Response($this->getCategories(), [], HttpResponse::HTTP_OK)
            )
        );

        $this->categoryConfiguration = $this->buildBasicConfiguration();
    }

    public function testNoCategories(): void
    {
        $this->expectExceptionObject(new NoCategoryResourcesException('No resource found.'));
        $payload = new CategoryPayload($this->createClient());

        /** @var CreateUpdateEntityTask $task */
        $task = $this->taskProvider->get(CreateUpdateEntityTask::class);
        $task->__invoke($payload);
    }

    public function testCreateCategories(): void
    {
        $retrieveCategoryPayload = new CategoryPayload($this->createClient());

        /** @var RetrieveCategoriesTask $task */
        $task = $this->taskProvider->get(RetrieveCategoriesTask::class);
        $payload = $task->__invoke($retrieveCategoryPayload);

        /** @var CreateUpdateEntityTask $task */
        $task = $this->taskProvider->get(CreateUpdateEntityTask::class);
        $task->__invoke($payload);

        $taxonRepository = self::$container->get('sylius.repository.taxon');

        $expectedTaxonToExists = [
            'sales',
            'acer',
            'led_tvs',
            'master',
            'master_men_blazers_deals',
            'clothes',
            'pants',
            'shoes',
            'sweat',
            'coats',
            'underwear',
        ];

        foreach ($expectedTaxonToExists as $expectedTaxonToExist) {
            $this->assertNotNull($taxonRepository->findOneBy(['code' => $expectedTaxonToExist]));
        }
    }

    public function testCreateCategoriesWithRootAndExclusions(): void
    {
        $this->categoryConfiguration->setRootCategory('clothes');
        $this->categoryConfiguration->setNotImportCategories(['clothes_accessories']);
        $this->manager->flush();

        $retrieveCategoryPayload = new CategoryPayload($this->createClient());

        /** @var RetrieveCategoriesTask $task */
        $task = $this->taskProvider->get(RetrieveCategoriesTask::class);
        $payload = $task->__invoke($retrieveCategoryPayload);

        /** @var CreateUpdateEntityTask $task */
        $task = $this->taskProvider->get(CreateUpdateEntityTask::class);
        $task->__invoke($payload);

        $taxonRepository = self::$container->get('sylius.repository.taxon');

        $expectedTaxonToExists = ['clothes', 'pants', 'shoes', 'sweat', 'coats', 'underwear'];

        foreach ($expectedTaxonToExists as $expectedTaxonToExist) {
            $this->assertNotNull($taxonRepository->findOneBy(['code' => $expectedTaxonToExist]));
        }
    }
}
