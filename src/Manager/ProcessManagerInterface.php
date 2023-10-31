<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Manager;

use Symfony\Component\Process\Process;

interface ProcessManagerInterface
{
    public function startAll(): void;

    public function addProcess(Process $process, callable $callback = null, array $env = []): self;

    public function setInstantProcessing(bool $instantProcessing): self;

    public function waitForAllProcesses(): void;

    public function setNumberOfParallelProcesses(int $numberOfParallelProcesses): void;
}
