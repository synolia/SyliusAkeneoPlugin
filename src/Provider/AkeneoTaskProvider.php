<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class AkeneoTaskProvider
{
    /** @var array<\Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface> */
    private $tasks;

    public function addTask(AkeneoTaskInterface $akeneoTask): void
    {
        $this->tasks[\get_class($akeneoTask)] = $akeneoTask;
    }

    public function get(string $taskClassName): AkeneoTaskInterface
    {
        if (!\array_key_exists($taskClassName, $this->tasks)) {
            throw new \RuntimeException('Unable to find task ' . $taskClassName);
        }

        return $this->tasks[$taskClassName];
    }
}
