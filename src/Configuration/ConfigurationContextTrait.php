<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Configuration;

use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Handler\Task\SymfonyProcessTaskHandler;

trait ConfigurationContextTrait
{
    private int $batchSize = 100;

    private bool $allowParallel = false;

    private bool $batchingAllowed = true;

    private int $maxRunningProcessQueueSize = 5;

    private bool $isContinue = false;

    private bool $processAsSoonAsPossible = true;

    private int $verbosity = OutputInterface::VERBOSITY_NORMAL;

    private array $filters = [];

    private string $handler = SymfonyProcessTaskHandler::HANDLER_CODE;

    private int $fromPage = 1;

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    public function allowParallel(): bool
    {
        return $this->allowParallel;
    }

    public function getMaxRunningProcessQueueSize(): int
    {
        return $this->maxRunningProcessQueueSize;
    }

    public function setBatchSize(int $batchSize): ConfigurationContextInterface
    {
        $this->batchSize = $batchSize;

        return $this;
    }

    public function setAllowParallel(bool $allowParallel): ConfigurationContextInterface
    {
        $this->allowParallel = $allowParallel;

        return $this;
    }

    public function setMaxRunningProcessQueueSize(int $maxRunningProcessQueueSize): ConfigurationContextInterface
    {
        $this->maxRunningProcessQueueSize = $maxRunningProcessQueueSize;

        return $this;
    }

    public function setBatchingAllowed(bool $batchingAllowed): ConfigurationContextInterface
    {
        $this->batchingAllowed = $batchingAllowed;

        return $this;
    }

    public function isBatchingAllowed(): bool
    {
        return $this->batchingAllowed;
    }

    public function getVerbosity(): int
    {
        return $this->verbosity;
    }

    public function getVerbosityArgument(): string
    {
        return match ($this->getVerbosity()) {
            OutputInterface::VERBOSITY_QUIET => '-q',
            OutputInterface::VERBOSITY_VERBOSE => '-v',
            OutputInterface::VERBOSITY_VERY_VERBOSE => '-vv',
            OutputInterface::VERBOSITY_DEBUG => '-vvv',
            default => '',
        };
    }

    public function setIsContinue(bool $isContinue): ConfigurationContextInterface
    {
        $this->isContinue = $isContinue;

        return $this;
    }

    public function isContinue(): bool
    {
        return $this->isContinue;
    }

    public function setProcessAsSoonAsPossible(bool $processAsSoonAsPossible): ConfigurationContextInterface
    {
        $this->processAsSoonAsPossible = $processAsSoonAsPossible;

        return $this;
    }

    public function getProcessAsSoonAsPossible(): bool
    {
        return $this->processAsSoonAsPossible;
    }

    public function disableBatching(): ConfigurationContextInterface
    {
        $this->processAsSoonAsPossible = false;
        $this->allowParallel = false;
        $this->batchingAllowed = false;

        return $this;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function setFilters(array $filters): ConfigurationContextInterface
    {
        $this->filters = $filters;

        return $this;
    }

    public function getHandler(): string
    {
        return $this->handler;
    }

    public function setHandler(string $handler): ConfigurationContextInterface
    {
        $this->handler = $handler;

        return $this;
    }

    public function getFromPage(): int
    {
        return $this->fromPage;
    }

    public function setFromPage(int $fromPage): ConfigurationContextInterface
    {
        $this->fromPage = $fromPage;

        return $this;
    }
}
