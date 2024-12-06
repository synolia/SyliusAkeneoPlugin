<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Handler\Task;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Synolia\SyliusAkeneoPlugin\Handler\Task\TaskHandlerInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;

class TaskHandlerProvider implements TaskHandlerProviderInterface
{
    /**
     * @param TaskHandlerInterface[] $taskHandlers
     */
    public function __construct(
        private LoggerInterface $akeneoLogger,
        #[TaggedIterator(TaskHandlerInterface::class)]
        private iterable $taskHandlers = [],
    ) {
    }

    public function provide(PipelinePayloadInterface $pipelinePayload): TaskHandlerInterface
    {
        foreach ($this->taskHandlers as $taskHandler) {
            if ($taskHandler->support($pipelinePayload)) {
                $this->akeneoLogger->debug($taskHandler::class);

                return $taskHandler;
            }
        }

        throw new \LogicException('No supported TaskHandler found');
    }
}
