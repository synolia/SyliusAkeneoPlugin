<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface PipelinePayloadInterface
{
    public function getAkeneoPimClient(): AkeneoPimEnterpriseClientInterface;

    public function getOutputInterface(): OutputInterface;

    public function setOutputInterface(OutputInterface $outputInterface): self;

    public function getType(): string;
}
