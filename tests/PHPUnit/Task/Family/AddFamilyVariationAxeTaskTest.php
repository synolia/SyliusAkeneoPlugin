<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Family;

use Akeneo\Pim\ApiClient\Api\FamilyVariantApi;
use Akeneo\Pim\ApiClient\Api\ProductModelApi;
use donatj\MockWebServer\Response;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroupInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Family\FamilyPayload;
use Synolia\SyliusAkeneoPlugin\Provider\TaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\Family\ProcessFamilyTask;
use Synolia\SyliusAkeneoPlugin\Task\SetupTask;
use Synolia\SyliusAkeneoPlugin\Task\TearDownTask;

/**
 * @internal
 * @coversNothing
 */
final class AddFamilyVariationAxeTaskTest extends AbstractTaskTest
{
    /** @var AkeneoTaskProvider */
    private $taskProvider;

    /** @var EntityRepository */
    private $productGroupRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskProvider = $this->getContainer()->get(TaskProvider::class);
        $this->productGroupRepository = $this->getContainer()->get('akeneo.repository.product_group');
        $this->server->setResponseOfPath(
            '/' . sprintf(FamilyVariantApi::FAMILY_VARIANT_URI, 'clothing', 'clothing_color_size'),
            new Response($this->getFileContent('family_variant_clothing_color_size.json'), [], HttpResponse::HTTP_OK)
        );
        $this->server->setResponseOfPath(
            '/' . sprintf(ProductModelApi::PRODUCT_MODELS_URI),
            new Response($this->getFileContent('product_models_caelus.json'), [], HttpResponse::HTTP_OK)
        );

        self::assertInstanceOf(TaskProvider::class, $this->taskProvider);
    }

    public function testAddOrUpdateProductModelTask(): void
    {
        $familyPayload = new FamilyPayload($this->createClient());
        $familyPayload->setProcessAsSoonAsPossible(false);

        $setupFamilyTask = $this->taskProvider->get(SetupTask::class);
        $familyPayload = $setupFamilyTask->__invoke($familyPayload);

        /** @var ProcessFamilyTask $processFamilyTask */
        $processFamilyTask = $this->taskProvider->get(ProcessFamilyTask::class);
        $processFamilyTask->__invoke($familyPayload);

        $tearDownFamilyTask = $this->taskProvider->get(TearDownTask::class);
        $tearDownFamilyTask->__invoke($familyPayload);

        /** @var ProductGroupInterface $productGroup */
        $productGroup = $this->productGroupRepository->findOneBy(['productParent' => 'caelus']);
        $this->assertNotNull($productGroup);
        $this->assertEquals('clothing', $productGroup->getFamily());
    }
}
