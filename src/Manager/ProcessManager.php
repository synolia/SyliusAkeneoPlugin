<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Manager;

use Synolia\SyliusAkeneoPlugin\ProcessManager\ProcessManager as BluePsyduckProcessManager;
use Symfony\Component\Process\Process;

class ProcessManager implements ProcessManagerInterface
{
    private array $processes = [];

    private bool $instantProcessing = false;

    public function __construct(
        protected BluePsyduckProcessManager $processManager,
    ) {
    }

    public function setNumberOfParallelProcesses(int $numberOfParallelProcesses): void
    {
        $this->processManager->setNumberOfParallelProcesses($numberOfParallelProcesses);
    }

    public function addProcess(Process $process, callable $callback = null, array $env = []): ProcessManagerInterface
    {
        if ($this->instantProcessing) {
            $this->processManager->addProcess($process, $callback, $env);

            return $this;
        }

        $this->processes[] = $process;

        return $this;
    }

    public function waitForAllProcesses(): void
    {
        $this->processManager->waitForAllProcesses();
    }

    public function startAll(): void
    {
        if ($this->instantProcessing) {
            $this->waitForAllProcesses();

            return;
        }

        foreach ($this->processes as $process) {
            $this->processManager->addProcess($process);
        }

        $this->waitForAllProcesses();
    }

    public function setInstantProcessing(bool $instantProcessing): self
    {
        $this->instantProcessing = $instantProcessing;

        return $this;
    }
}
