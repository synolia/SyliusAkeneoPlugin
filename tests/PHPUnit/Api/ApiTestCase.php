<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Api;

use Akeneo\Pim\ApiClient\Api\AuthenticationApi;
use Akeneo\Pim\ApiClient\Api\LocaleApi;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientBuilder;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use donatj\MockWebServer\MockWebServer;
use donatj\MockWebServer\Response;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\AbstractKernelTestCase;
use Webmozart\Assert\Assert;

abstract class ApiTestCase extends AbstractKernelTestCase
{
    private const SAMPLE_PATH = '/datas/sample/';

    /** @var MockWebServer */
    protected $server;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    protected $manager;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->manager = $this->getContainer()->get('doctrine')->getManager();
        $this->server = new MockWebServer((int) $_SERVER['MOCK_SERVER_PORT'], $_SERVER['MOCK_SERVER_HOST']);
        $this->server->start();
        $this->server->setResponseOfPath(
            '/' . AuthenticationApi::TOKEN_URI,
            new Response($this->getAuthenticatedJson()),
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(LocaleApi::LOCALES_URI),
            new Response($this->getFileContent('locales.json'), [], HttpResponse::HTTP_OK),
        );
    }

    protected function tearDown(): void
    {
        $this->server->stop();
        parent::tearDown();
    }

    protected function initSyliusLocales(): void
    {
        $localesToAdd = [
            'fr_FR',
            'en_US',
            'de_DE',
        ];

        /** @var string $localeCode */
        foreach ($localesToAdd as $localeCode) {
            /** @var LocaleInterface $locale */
            $locale = $this->getContainer()->get('sylius.repository.locale')->findOneBy(['code' => $localeCode]);

            if ($locale instanceof LocaleInterface) {
                $this->assignLocaleToChannels($locale);

                continue;
            }

            /** @var FactoryInterface $localeFactory */
            $localeFactory = $this->getContainer()->get('sylius.factory.locale');

            /** @var LocaleInterface $locale */
            $locale = $localeFactory->createNew();
            $locale->setCode($localeCode);
            $this->manager->persist($locale);
            $this->assignLocaleToChannels($locale);
        }
    }

    private function assignLocaleToChannels(LocaleInterface $locale): void
    {
        $channels = $this->getContainer()->get('sylius.repository.channel')->findAll();

        /** @var ChannelInterface $channel */
        foreach ($channels as $channel) {
            $channel->addLocale($locale);
        }
    }

    protected function createClient(): AkeneoPimEnterpriseClientInterface
    {
        $clientBuilder = new AkeneoPimEnterpriseClientBuilder($this->server->getServerRoot());

        return $clientBuilder->buildAuthenticatedByPassword(
            'client_id',
            'secret',
            'username',
            'password',
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

        $content = file_get_contents($file);
        if (false === $content) {
            return '';
        }

        return $content;
    }

    protected function getAuthenticatedJson(): string
    {
        return <<<'JSON'
                        {
                            "refresh_token" : "refresh-token",
                            "access_token" : "access-token"
                        }
            JSON;
    }
}
