<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;

abstract class AbstractPayload implements PipelinePayloadInterface
{
    /** @var \Akeneo\Pim\ApiClient\AkeneoPimClientInterface */
    protected $akeneoPimClient;

    /** @var \Symfony\Component\Console\Output\OutputInterface */
    protected $outputInterface;

    public function __construct(AkeneoPimClientInterface $akeneoPimClient)
    {
        $this->akeneoPimClient = $akeneoPimClient;
    }

    public function getAkeneoPimClient(): AkeneoPimClientInterface
    {
        return $this->akeneoPimClient;
    }

    public function getOutputInterface(): OutputInterface
    {
        return $this->outputInterface;
    }

    public function setOutputInterface(OutputInterface $outputInterface): self
    {
        $this->outputInterface = $outputInterface;

        return $this;
    }
}
