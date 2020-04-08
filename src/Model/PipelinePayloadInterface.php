<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Model;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;

interface PipelinePayloadInterface
{
    public function getAkeneoPimClient(): AkeneoPimClientInterface;
}
