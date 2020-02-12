<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Mock;

use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\AuthenticationApi;
use donatj\MockWebServer\MockWebServer;
use donatj\MockWebServer\Response;
use donatj\MockWebServer\ResponseStack;
use Webmozart\Assert\Assert;

final class AkeneoMock
{
    private const SAMPLE_PATH = '/datas/sample/';

    /** @var MockWebServer */
    public $server;

    public function __construct()
    {
        $this->server = new MockWebServer(8081, '127.0.0.1');
        $this->server->start();

        $this->server->setResponseOfPath(
            '/' . AuthenticationApi::TOKEN_URI,
            new ResponseStack(
                new Response($this->getAuthenticatedJson())
            )
        );
    }

    public function createClient(): AkeneoPimClientInterface
    {
        $clientBuilder = new AkeneoPimClientBuilder($this->server->getServerRoot());

        return $clientBuilder->buildAuthenticatedByPassword(
            'client_id',
            'secret',
            'username',
            'password'
        );
    }

    public function getFileContent(string $name): string
    {
        $file = self::getSamplePath() . $name;
        Assert::fileExists($file);

        $content = \file_get_contents($file);
        if (false === $content) {
            return '';
        }

        return $content;
    }

    public static function getSamplePath(): string
    {
        return \dirname(__DIR__) . '/../tests/PHPUnit' . self::SAMPLE_PATH;
    }

    private function getAuthenticatedJson(): string
    {
        return <<<JSON
            {
                "refresh_token" : "refresh-token",
                "access_token" : "access-token"
            }
JSON;
    }
}
