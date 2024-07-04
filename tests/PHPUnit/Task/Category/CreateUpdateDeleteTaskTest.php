<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Category;

use Akeneo\Pim\ApiClient\Api\CategoryApi;
use donatj\MockWebServer\Response;
use donatj\MockWebServer\ResponseStack;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\CategoryConfigurationProviderInterface;
use Synolia\SyliusAkeneoPlugin\Task\Category\ProcessCategoriesTask;
use Synolia\SyliusAkeneoPlugin\Task\SetupTask;

/**
 * @internal
 *
 * @coversNothing
 */
final class CreateUpdateDeleteTaskTest extends AbstractTaskTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->server->setResponseOfPath(
            '/' . sprintf(CategoryApi::CATEGORIES_URI),
            new ResponseStack(
                new Response($this->getCategories(), [], HttpResponse::HTTP_OK),
            ),
        );

        $this->categoryConfiguration = $this->buildBasicConfiguration();
    }

    public function testCreateCategories(): void
    {
        /** @var CategoryConfigurationProviderInterface $configuration */
        $configuration = $this->getContainer()->get(CategoryConfigurationProviderInterface::class);
        $configuration->get()->setCategoryCodesToImport(['master', 'sales']);

        $payload = new CategoryPayload($this->createClient());

        $setupAttributeTask = $this->taskProvider->get(SetupTask::class);
        $payload = $setupAttributeTask->__invoke($payload);

        /** @var ProcessCategoriesTask $task */
        $task = $this->taskProvider->get(ProcessCategoriesTask::class);
        $task->__invoke($payload);

        $taxonRepository = $this->getContainer()->get('sylius.repository.taxon');

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
        $this->categoryConfiguration->setRootCategories(['clothes']);
        $this->categoryConfiguration->setNotImportCategories(['clothes_accessories']);
        $this->manager->flush();

        $payload = new CategoryPayload($this->createClient());

        $setupAttributeTask = $this->taskProvider->get(SetupTask::class);
        $payload = $setupAttributeTask->__invoke($payload);

        /** @var ProcessCategoriesTask $task */
        $task = $this->taskProvider->get(ProcessCategoriesTask::class);
        $task->__invoke($payload);

        $taxonRepository = $this->getContainer()->get('sylius.repository.taxon');

        $expectedTaxonToExists = ['clothes', 'pants', 'shoes', 'sweat', 'coats', 'underwear'];

        foreach ($expectedTaxonToExists as $expectedTaxonToExist) {
            $this->assertNotNull($taxonRepository->findOneBy(['code' => $expectedTaxonToExist]));
        }
    }
}
