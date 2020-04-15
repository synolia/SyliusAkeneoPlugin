<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Pipeline;

use GrumPHP\Event\Dispatcher\EventDispatcherInterface;
use League\Pipeline\Pipeline;
use Synolia\SyliusAkeneoPlugin\Event\AfterTaskEvent;
use Synolia\SyliusAkeneoPlugin\Event\BeforeTaskEvent;
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Pipeline\Processor;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\DummyTask;
use Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Api\ApiTestCase;

class PipelineTest extends ApiTestCase
{
    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /** @var AkeneoTaskProvider */
    protected $taskProvider;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->dispatcher = self::$container->get('event_dispatcher');
        $this->taskProvider = self::$container->get(AkeneoTaskProvider::class);
    }

    public function testPipelineEvent(): void
    {
        $pipeline = new Pipeline(new Processor($this->dispatcher));
        $pipeline = $pipeline->pipe($this->taskProvider->get(DummyTask::class));

        /** @var DummyPayload $payload */
        $payload = $pipeline->process(new DummyPayload($this->createClient()));

        $this->assertInstanceOf(PipelinePayloadInterface::class, $payload);
        $this->assertContains(BeforeTaskEvent::NAME, $payload->getLogs());
        $this->assertContains(AfterTaskEvent::NAME, $payload->getLogs());
    }
}
