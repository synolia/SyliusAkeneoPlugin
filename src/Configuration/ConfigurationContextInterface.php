<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Configuration;

interface ConfigurationContextInterface
{
    public function getBatchSize(): int;

    public function allowParallel(): bool;

    public function getMaxRunningProcessQueueSize(): int;

    public function setBatchSize(int $batchSize): self;

    public function setAllowParallel(bool $allowParallel): self;

    public function setMaxRunningProcessQueueSize(int $maxRunningProcessQueueSize): self;

    public function setBatchingAllowed(bool $batchingAllowed): self;

    public function isBatchingAllowed(): bool;

    public function getVerbosity(): int;

    public function getVerbosityArgument(): string;

    public function setIsContinue(bool $isContinue): self;

    public function isContinue(): bool;

    public function setProcessAsSoonAsPossible(bool $processAsSoonAsPossible): self;

    public function getProcessAsSoonAsPossible(): bool;

    public function disableBatching(): self;

    public function getFilters(): array;

    public function setFilters(array $filters): self;

    public function getHandler(): string;

    public function setHandler(string $handler): self;

    public function getFromPage(): int;

    public function setFromPage(int $fromPage): self;
}
