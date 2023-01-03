<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Synolia\SyliusAkeneoPlugin\Command\Context\CommandContextInterface;

final class FilterEvent extends Event
{
    private array $filters = [];

    public function __construct(private CommandContextInterface $commandContext)
    {
    }

    public function getCommandContext(): CommandContextInterface
    {
        return $this->commandContext;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function addFilter(string $key, array $value): void
    {
        $this->filters[$key] = $value;
    }
}
