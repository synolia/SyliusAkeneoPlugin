<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

abstract class AbstractBatchCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('ids', InputArgument::REQUIRED, 'Comma separated list of ids');
        $this->setHidden(true);
    }
}
