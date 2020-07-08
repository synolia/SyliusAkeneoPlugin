<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Pipeline;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;

final class DummyPayload implements PipelinePayloadInterface
{
    /** @var AkeneoPimClientInterface */
    private $akeneoPimClient;

    /** @var array */
    private $logs = [];

    public function __construct(AkeneoPimClientInterface $akeneoPimClient)
    {
        $this->akeneoPimClient = $akeneoPimClient;
    }

    public function getAkeneoPimClient(): AkeneoPimClientInterface
    {
        return $this->akeneoPimClient;
    }

    public function getType(): string
    {
        return 'Dummy';
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function addLog(string $log): self
    {
        $this->logs[] = $log;

        return $this;
    }

    public function getOutputInterface(): OutputInterface
    {
        return new NullOutput();
    }

    public function setOutputInterface(OutputInterface $outputInterface): PipelinePayloadInterface
    {
        return $this;
    }
}
