<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface PipelinePayloadInterface
{
    public function getAkeneoPimClient(): AkeneoPimClientInterface;

    public function getOutputInterface(): OutputInterface;

    public function setOutputInterface(OutputInterface $outputInterface): self;

    public function getType(): string;
}
