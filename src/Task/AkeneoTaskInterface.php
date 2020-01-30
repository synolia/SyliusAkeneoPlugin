<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task;

use Synolia\SyliusAkeneoPlugin\Model\AkeneoPipelinePayload;

interface AkeneoTaskInterface
{
    public const TAG_ID = 'sylius.akeneo_pipeline.task';

    public function __invoke(AkeneoPipelinePayload $payload): AkeneoPipelinePayload;
}
