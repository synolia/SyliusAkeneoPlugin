<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;

interface PipelinePayloadInterface extends PayloadInterface
{
    public function getAkeneoPimClient(): AkeneoPimClientInterface;

    public function getType(): string;
}
