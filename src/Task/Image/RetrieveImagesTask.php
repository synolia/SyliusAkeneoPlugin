<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Image;

use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class RetrieveImagesTask implements AkeneoTaskInterface
{
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        return $payload;
    }
}
