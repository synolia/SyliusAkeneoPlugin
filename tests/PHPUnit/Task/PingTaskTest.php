<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task;

use Symfony\Component\Console\Output\BufferedOutput;
use Synolia\SyliusAkeneoPlugin\Model\AkeneoPipelinePayload;
use Synolia\SyliusAkeneoPlugin\Task\PingTask;

/**
 * @internal
 */
final class PingTaskTest extends AbstractTaskTestCase
{
    /** @var PingTask */
    private $task;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var PingTask $task */
        $task = self::$container->get(PingTask::class);
        self::assertInstanceOf(PingTask::class, $task);

        $this->task = $task;
    }

    /**
     * @dataProvider providePayload
     */
    public function testPing(AkeneoPipelinePayload $payload): void
    {
        $this->task->__invoke($payload);

        /** @var BufferedOutput $output */
        $output = $payload->getOutput();

        self::assertStringContainsString('Pong', $output->fetch());
    }
}
