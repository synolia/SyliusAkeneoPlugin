<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task;

use Synolia\SyliusAkeneoPlugin\Model\AkeneoPipelinePayload;

final class PingTask implements AkeneoTaskInterface
{
    public function __invoke(AkeneoPipelinePayload $payload): AkeneoPipelinePayload
    {
        $payload->getOutput()->writeln('Pong');

        return $payload;
    }
}
