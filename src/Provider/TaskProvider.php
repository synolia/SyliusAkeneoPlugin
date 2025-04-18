<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class TaskProvider
{
    public function __construct(
        /** @var iterable<AkeneoTaskInterface> $tasks */
        #[AutowireIterator(AkeneoTaskInterface::class)]
        private iterable $tasks,
    ) {
    }

    public function get(string $taskClassName): AkeneoTaskInterface
    {
        foreach ($this->tasks as $task) {
            if ($task::class === $taskClassName) {
                return $task;
            }
        }

        throw new RuntimeException('Unable to find task ' . $taskClassName);
    }
}
