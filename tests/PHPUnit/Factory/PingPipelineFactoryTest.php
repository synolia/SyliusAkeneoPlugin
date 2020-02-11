<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Factory;

use League\Pipeline\Pipeline;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Synolia\SyliusAkeneoPlugin\Factory\PingPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Model\AkeneoPipelinePayload;

class PingPipelineFactoryTest extends KernelTestCase
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

    public function testCreatePipeline(): void
    {
        $pipeline = $this->factory->create();

        self::assertInstanceOf(Pipeline::class, $pipeline);
    }

    public function testProcessPipeline(): void
    {
        $pipeline = $this->factory->create();
        $paylodIn = new AkeneoPipelinePayload(new BufferedOutput());

        /** @var AkeneoPipelinePayload $paylodOut */
        $paylodOut = $pipeline->process($paylodIn);
        $this->assertInstanceOf(AkeneoPipelinePayload::class, $paylodOut);

        /** @var BufferedOutput $output */
        $output = $paylodOut->getOutput();
        self::assertInstanceOf(BufferedOutput::class, $output);
        self::assertStringContainsString('Pong', $output->fetch());
    }
}
