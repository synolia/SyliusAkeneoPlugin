<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;

#[AutoconfigureTag]
interface AkeneoTaskInterface
{
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface;
}
