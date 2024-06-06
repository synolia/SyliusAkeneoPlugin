<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Association;

use Synolia\SyliusAkeneoPlugin\Message\Batch\BatchMessageInterface;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class AssociationPayload extends AbstractPayload
{
    public function createBatchMessage(array $items): BatchMessageInterface
    {
        throw new \InvalidArgumentException();
    }
}
