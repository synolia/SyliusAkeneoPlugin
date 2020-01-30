<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Synolia\SyliusAkeneoPlugin\Model\AkeneoPipelinePayload;

/**
 * @internal
 */
abstract class AbstractTaskTestCase extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function providePayload(): \Generator
    {
        $this->setUp();

        yield [new AkeneoPipelinePayload(new BufferedOutput())];
    }
}
