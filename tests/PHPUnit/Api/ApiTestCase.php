<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Api;

use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\AuthenticationApi;
use donatj\MockWebServer\MockWebServer;
use donatj\MockWebServer\Response;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Webmozart\Assert\Assert;

abstract class ApiTestCase extends KernelTestCase
{
    private const SAMPLE_PATH = '/datas/sample/';

    /** @var MockWebServer */
    protected $server;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    protected $manager;

    /** @var ApiConfiguration */
    protected $apiConfiguration;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->manager = self::$container->get('doctrine')->getManager();
        $this->server = new MockWebServer((int) $_SERVER['MOCK_SERVER_PORT'], $_SERVER['MOCK_SERVER_HOST']);
        $this->server->start();
        $this->server->setResponseOfPath(
            '/' . AuthenticationApi::TOKEN_URI,
            new Response($this->getAuthenticatedJson())
        );
    }

    protected function tearDown(): void
    {
        $this->server->stop();
        parent::tearDown();
    }

    public function initializeApiConfiguration(): void
    {
        $this->apiConfiguration = new ApiConfiguration();
        $this->apiConfiguration->setPaginationSize(100)
            ->setBaseUrl('test')
            ->setUsername('test')
            ->setApiClientId('test')
            ->setApiClientSecret('test')
            ->setIsEnterprise(false)
            ->setPassword('test')
        ;
        $this->manager->persist($this->apiConfiguration);
    }

    protected function createClient(): AkeneoPimClientInterface
    {
        $clientBuilder = new AkeneoPimClientBuilder($this->server->getServerRoot());

        return $clientBuilder->buildAuthenticatedByPassword(
            'client_id',
            'secret',
            'username',
            'password'
        );
    }

    protected static function getSamplePath(): string
    {
        return \dirname(__DIR__) . self::SAMPLE_PATH;
    }

    protected function getFileContent(string $name): string
    {
        $file = self::getSamplePath() . $name;
        Assert::fileExists($file);

        $content = \file_get_contents($file);
        if (false === $content) {
            return '';
        }

        return $content;
    }

    protected function getAuthenticatedJson(): string
    {
        return <<<JSON
            {
                "refresh_token" : "refresh-token",
                "access_token" : "access-token"
            }
JSON;
    }
}
