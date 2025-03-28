<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use RuntimeException;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class TaskProvider
{
    /** @var array<AkeneoTaskInterface> */
    private array $tasks = [];

    public function addTask(AkeneoTaskInterface $akeneoTask): void
    {
        $this->tasks[$akeneoTask::class] = $akeneoTask;
    }

    public function get(string $taskClassName): AkeneoTaskInterface
    {
        if (!\array_key_exists($taskClassName, $this->tasks)) {
            throw new RuntimeException('Unable to find task ' . $taskClassName);
        }

        return $this->tasks[$taskClassName];
    }
}
