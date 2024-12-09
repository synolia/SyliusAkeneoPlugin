<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload;

use Synolia\SyliusAkeneoPlugin\Exceptions\NotImplementedException;
use Synolia\SyliusAkeneoPlugin\Message\Batch\BatchMessageInterface;

final class ConfigurationPayload extends AbstractPayload implements PipelinePayloadInterface
{
    public function createBatchMessage(array $items): BatchMessageInterface
    {
        throw new NotImplementedException();
    }
}
