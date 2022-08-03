<?php

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Checker;

use Synolia\SyliusAkeneoPlugin\Checker\EditionCheckerInterface;
use Synolia\SyliusAkeneoPlugin\Config\AkeneoEditionEnum;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\AbstractKernelTestCase;

class EditionCheckerTest extends AbstractKernelTestCase
{
    private ?ApiConfiguration $apiConfiguration = null;
    private ?EditionCheckerInterface $editionChecker = null;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->manager = $this->getContainer()->get('doctrine')->getManager();
        $this->manager->beginTransaction();

        $this->apiConfiguration = new ApiConfiguration();
        $this->apiConfiguration
            ->setPaginationSize(100)
            ->setBaseUrl(sprintf('%s:%d', $_SERVER['MOCK_SERVER_HOST'], (int) $_SERVER['MOCK_SERVER_PORT']))
            ->setUsername('test')
            ->setApiClientId('test')
            ->setApiClientSecret('test')
            ->setEdition(AkeneoEditionEnum::ENTERPRISE)
            ->setPassword('test')
        ;

        $this->manager->persist($this->apiConfiguration);
        $this->manager->flush();

        $this->editionChecker = $this->getContainer()->get(EditionCheckerInterface::class);
    }

    public function testIsCommunity(): void
    {
        $this->apiConfiguration->setEdition(AkeneoEditionEnum::COMMUNITY);
        $this->manager->flush();

        self::assertTrue($this->editionChecker->isCommunityEdition());
        self::assertFalse($this->editionChecker->isEnterprise());
        self::assertFalse($this->editionChecker->isGrowthEdition());
        self::assertFalse($this->editionChecker->isSerenityEdition());
    }

    public function testIsEnterprise(): void
    {
        $this->apiConfiguration->setEdition(AkeneoEditionEnum::ENTERPRISE);
        $this->manager->flush();

        self::assertFalse($this->editionChecker->isCommunityEdition());
        self::assertTrue($this->editionChecker->isEnterprise());
        self::assertFalse($this->editionChecker->isGrowthEdition());
        self::assertFalse($this->editionChecker->isSerenityEdition());
    }

    public function testIsGrowth(): void
    {
        $this->apiConfiguration->setEdition(AkeneoEditionEnum::GROWTH);
        $this->manager->flush();

        self::assertFalse($this->editionChecker->isCommunityEdition());
        self::assertFalse($this->editionChecker->isEnterprise());
        self::assertTrue($this->editionChecker->isGrowthEdition());
        self::assertFalse($this->editionChecker->isSerenityEdition());
    }

    public function testIsSerenity(): void
    {
        $this->apiConfiguration->setEdition(AkeneoEditionEnum::SERENITY);
        $this->manager->flush();

        self::assertFalse($this->editionChecker->isCommunityEdition());
        self::assertFalse($this->editionChecker->isEnterprise());
        self::assertFalse($this->editionChecker->isGrowthEdition());
        self::assertTrue($this->editionChecker->isSerenityEdition());
    }
}
