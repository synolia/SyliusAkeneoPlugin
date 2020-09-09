<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use ReflectionClass;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractPayload implements PipelinePayloadInterface
{
    /** @var \Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface */
    protected $akeneoPimClient;

    /** @var \Symfony\Component\Console\Output\OutputInterface */
    protected $outputInterface;

    public function __construct(AkeneoPimEnterpriseClientInterface $akeneoPimClient)
    {
        $this->akeneoPimClient = $akeneoPimClient;
    }

    public function getAkeneoPimClient(): AkeneoPimEnterpriseClientInterface
    {
        return $this->akeneoPimClient;
    }

    public function getOutputInterface(): OutputInterface
    {
        return $this->outputInterface;
    }

    public function setOutputInterface(OutputInterface $outputInterface): PipelinePayloadInterface
    {
        $this->outputInterface = $outputInterface;

        return $this;
    }

    public function getType(): string
    {
        try {
            return \mb_substr((new ReflectionClass($this))->getShortName(), 0, -7);
        } catch (\ReflectionException $e) {
            return '';
        }
    }
}
