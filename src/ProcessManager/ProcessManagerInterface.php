<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\ProcessManager;

use Symfony\Component\Process\Process;

/**
 * The interface of the process manager.
 *
 * @thanks BluePsyduck <bluepsyduck@gmx.com>
 *
 * @see https://github.com/BluePsyduck/symfony-process-manager
 */
interface ProcessManagerInterface
{
    /**
     * Adds a process to the manager.
     *
     * @param Process<string> $process
     * @param array<mixed> $env
     *
     * @return $this
     */
    public function addProcess(Process $process, callable $callback = null, array $env = []);

    /**
     * Waits for all processes to be finished.
     *
     * @return $this
     */
    public function waitForAllProcesses();

    /**
     * Returns whether the manager still has unfinished processes.
     */
    public function hasUnfinishedProcesses(): bool;
}
