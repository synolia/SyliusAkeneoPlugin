<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use ReflectionClass;
use ReflectionException;
use Synolia\SyliusAkeneoPlugin\Command\Context\CommandContextInterface;
use Synolia\SyliusAkeneoPlugin\Configuration\ConfigurationContextTrait;
use Synolia\SyliusAkeneoPlugin\Exceptions\Payload\CommandContextIsNullException;

abstract class AbstractPayload implements PipelinePayloadInterface
{
    use ConfigurationContextTrait;

    protected array $ids = [];

    protected string $tmpTableName;

    protected string $commandName;

    protected array $customFilters = [];

    public function __construct(
        protected AkeneoPimClientInterface $akeneoPimClient,
        protected ?CommandContextInterface $commandContext = null,
    ) {
        if ($commandContext instanceof CommandContextInterface) {
            $this->allowParallel = $commandContext->allowParallel();
            $this->batchSize = $commandContext->getBatchSize();
            $this->batchingAllowed = $commandContext->isBatchingAllowed();
            $this->maxRunningProcessQueueSize = $commandContext->getMaxRunningProcessQueueSize();
            $this->verbosity = $commandContext->getVerbosity();
            $this->isContinue = $commandContext->isContinue();
            $this->processAsSoonAsPossible = $commandContext->getProcessAsSoonAsPossible();
            $this->handler = $commandContext->getHandler();
            $this->fromPage = $commandContext->getFromPage();
        }
    }

    public function getAkeneoPimClient(): AkeneoPimClientInterface
    {
        return $this->akeneoPimClient;
    }

    public function getType(): string
    {
        try {
            return mb_substr((new ReflectionClass($this))->getShortName(), 0, -7);
        } catch (ReflectionException) {
            return '';
        }
    }

    /**
     * @throws CommandContextIsNullException
     */
    public function getCommandContext(): CommandContextInterface
    {
        if (!$this->commandContext instanceof CommandContextInterface) {
            throw new CommandContextIsNullException();
        }

        return $this->commandContext;
    }

    public function hasCommandContext(): bool
    {
        return $this->commandContext instanceof CommandContextInterface;
    }

    public function getIds(): array
    {
        return $this->ids;
    }

    public function setIds(array $ids): BatchPayloadInterface
    {
        $this->ids = $ids;

        return $this;
    }

    public function getTmpTableName(): string
    {
        return $this->tmpTableName;
    }

    public function setTmpTableName(string $table): BatchPayloadInterface
    {
        $this->tmpTableName = $table;

        return $this;
    }

    public function getCommandName(): string
    {
        return $this->commandName;
    }

    public function setCommandName(string $command): BatchPayloadInterface
    {
        $this->commandName = $command;

        return $this;
    }

    public function getCustomFilters(): array
    {
        return $this->customFilters;
    }

    public function setCustomFilters(array $customFilters = []): void
    {
        $this->customFilters = $customFilters;
    }
}
