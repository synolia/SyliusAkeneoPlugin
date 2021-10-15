<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

abstract class AbstractBatchCommand extends Command
{
    /** @var string The default command description */
    protected static string $defaultDescription;

    protected function configure(): void
    {
        $this->setDescription(static::$defaultDescription);
        $this->addArgument('ids', InputArgument::REQUIRED, 'Comma separated list of ids');
        $this->setHidden(true);
    }
}
