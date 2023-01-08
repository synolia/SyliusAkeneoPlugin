<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;

interface PayloadFactoryInterface
{
    public function create(string $className): PipelinePayloadInterface;

    public function createFromCommand(
        string $className,
        InputInterface $input,
        OutputInterface $output,
    ): PipelinePayloadInterface;
}
