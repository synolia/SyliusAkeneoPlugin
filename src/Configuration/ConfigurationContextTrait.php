<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Configuration;

use Symfony\Component\Console\Output\OutputInterface;

trait ConfigurationContextTrait
{
    private int $batchSize = 100;

    private bool $allowParallel = false;

    private bool $batchingAllowed = true;

    private int $maxRunningProcessQueueSize = 5;

    private bool $isContinue = false;

    private bool $processAsSoonAsPossible = true;

    private int $verbosity = OutputInterface::VERBOSITY_NORMAL;

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
        switch ($this->getVerbosity()) {
            case OutputInterface::VERBOSITY_QUIET:
                return '-q';
            case OutputInterface::VERBOSITY_VERBOSE:
                return '-v';
            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                return '-vv';
            case OutputInterface::VERBOSITY_DEBUG:
                return '-vvv';
        }

        return '';
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
}
