<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Factory;

use League\Pipeline\Pipeline;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Synolia\SyliusAkeneoPlugin\Factory\FullImportPipelineFactory;

class FullImportPipelineFactoryTest extends KernelTestCase
{
    /** @var FullImportPipelineFactory */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        /** @var FullImportPipelineFactory $factory */
        $factory = self::$container->get(FullImportPipelineFactory::class);
        self::assertInstanceOf(FullImportPipelineFactory::class, $factory);

        $this->factory = $factory;
    }

    public function testProcessPipeline(): void
    {
        /** @var Pipeline $pipeline */
        $pipeline = $this->factory->createFullImportPipeline();
        self::assertInstanceOf(Pipeline::class, $pipeline);

        // TODO later when all pipelines will be implemented
    }
}
