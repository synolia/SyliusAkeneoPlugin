<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;

interface PipelinePayloadInterface extends PayloadInterface
{
    public function getAkeneoPimClient(): AkeneoPimEnterpriseClientInterface;

    public function getType(): string;
}
