<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Factory;

use League\Pipeline\Pipeline;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Synolia\SyliusAkeneoPlugin\Factory\PingPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Payload\FakePayload;

class CategoriesPipelineFactoryTest extends KernelTestCase
{
    /** @var PingPipelineFactory */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        /** @var PingPipelineFactory $factory */
        $factory = self::$container->get(PingPipelineFactory::class);
        self::assertInstanceOf(PingPipelineFactory::class, $factory);

        $this->factory = $factory;
    }

    public function testProcessPipeline(): void
    {
        /** @var Pipeline $pipeline */
        $pipeline = $this->factory->createImportCategoriesPipeline();
        $pipeline->process(new FakePayload());
    }
}
