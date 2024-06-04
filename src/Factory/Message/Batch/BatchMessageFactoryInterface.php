<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Factory\Message\Batch;

use Synolia\SyliusAkeneoPlugin\Message\Batch\BatchMessageInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;

interface BatchMessageFactoryInterface
{
    public static function createFromPayload(PipelinePayloadInterface $payload, array $items): BatchMessageInterface;
}
