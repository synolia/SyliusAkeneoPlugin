<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Asset;

use Akeneo\Pim\ApiClient\Api\AssetManager\AssetAttributeApi;
use Akeneo\Pim\ApiClient\Api\AssetManager\AssetFamilyApi;
use donatj\MockWebServer\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Entity\Asset;
use Synolia\SyliusAkeneoPlugin\Payload\Asset\AssetPayload;
use Synolia\SyliusAkeneoPlugin\Task\Asset\ProcessAssetTask;
use Synolia\SyliusAkeneoPlugin\Task\SetupTask;
use Synolia\SyliusAkeneoPlugin\Task\TearDownTask;

/**
 * @internal
 *
 * @coversNothing
 */
final class ProcessAssetsTaskTest extends AbstractTaskTest
{
    private const ASSET_COUNT = 8;

    private const LOCALE_COUNT = 3;

    public function testMediaLinkAsset(): void
    {
        $mockedAssetAttributesJson = $this->getFileContent('asset_manager_attributes_all.json');

        $this->server->setResponseOfPath(
            '/' . sprintf(AssetFamilyApi::ASSET_FAMILIES_URI),
            new Response($this->getFileContent('asset_families.json'), [], HttpResponse::HTTP_OK),
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(AssetFamilyApi::ASSET_FAMILY_URI, 'bynder'),
            new Response($this->getFileContent('asset_family.json'), [], HttpResponse::HTTP_OK),
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(\Akeneo\Pim\ApiClient\Api\AssetManager\AssetApi::ASSETS_URI, 'bynder'),
            new Response($this->getFileContent('asset_manager_api_all.json'), [], HttpResponse::HTTP_OK),
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(AssetAttributeApi::ASSET_ATTRIBUTES_URI, 'bynder'),
            new Response($mockedAssetAttributesJson, [], HttpResponse::HTTP_OK),
        );

        $payload = new AssetPayload($this->createClient());
        $payload->disableBatching();
        $setupAttributeTask = $this->taskProvider->get(SetupTask::class);
        $setupPayload = $setupAttributeTask->__invoke($payload);

        /** @var ProcessAssetTask $task */
        $task = $this->taskProvider->get(ProcessAssetTask::class);
        $payload = $task->__invoke($setupPayload);

        $tearDownAttributeTask = $this->taskProvider->get(TearDownTask::class);
        $tearDownAttributeTask->__invoke($payload);

        $assetRepository = static::getContainer()->get('akeneo.repository.asset');
        self::assertEquals(self::ASSET_COUNT * self::LOCALE_COUNT, $assetRepository->count([]));

        /** @var Asset $asset */
        $asset = $assetRepository->findOneBy([
            'familyCode' => 'bynder',
            'attributeCode' => 'media_url',
            'type' => 'media_link',
            'locale' => 'fr_FR',
            'scope' => 'ecommerce',
            'assetCode' => '02B89FF4_DD7B_4325_9ECA204E3D993B91',
        ]);

        self::assertIsArray($asset->getContent());
        self::assertArrayHasKey('url', $asset->getContent());
        self::assertEquals('http://akeneo.example.com/m/abc/def.jpg', $asset->getContent()['url']);
    }
}
