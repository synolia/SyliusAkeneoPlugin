<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Pipeline;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;

final class DummyPayload implements PipelinePayloadInterface
{
    /** @var AkeneoPimEnterpriseClientInterface */
    private $akeneoPimClient;

    /** @var array */
    private $logs = [];

    public function __construct(AkeneoPimEnterpriseClientInterface $akeneoPimClient)
    {
        $this->akeneoPimClient = $akeneoPimClient;
    }

    public function getAkeneoPimClient(): AkeneoPimEnterpriseClientInterface
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
