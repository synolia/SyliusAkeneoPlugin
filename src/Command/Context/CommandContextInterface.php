<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command\Context;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Configuration\ConfigurationContextInterface;

interface CommandContextInterface extends ConfigurationContextInterface
{
    public function getInput(): InputInterface;

    public function getOutput(): OutputInterface;

    public function getQuestionHelper(): QuestionHelper;
}
