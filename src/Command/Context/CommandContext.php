<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command\Context;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Configuration\ConfigurationContextTrait;

final class CommandContext implements CommandContextInterface
{
    use ConfigurationContextTrait;

    public InputInterface $input;

    public OutputInterface $output;

    public function __construct(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->input = $input;
        $this->output = $output;
        $this->verbosity = $this->output->getVerbosity();
    }

    public function isContinue(): bool
    {
        return $this->input->getOption('continue');
    }

    public function getInput(): InputInterface
    {
        return $this->input;
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }
}
