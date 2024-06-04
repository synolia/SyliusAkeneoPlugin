<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Handler\Task;

use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;

#[AutoconfigureTag()]
interface TaskHandlerInterface
{
    public function support(PipelinePayloadInterface $pipelinePayload): bool;

    public function setUp(PipelinePayloadInterface $pipelinePayload): PipelinePayloadInterface;

    public function tearDown(PipelinePayloadInterface $pipelinePayload): PipelinePayloadInterface;

    public function batch(
        PipelinePayloadInterface $pipelinePayload,
        array $items,
    ): void;

    public function handle(
        PipelinePayloadInterface $pipelinePayload,
        ResourceCursorInterface|PageInterface $handleType,
    ): void;

    public function continue(PipelinePayloadInterface $pipelinePayload): void;
}
