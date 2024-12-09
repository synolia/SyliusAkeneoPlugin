<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Asset;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Synolia\SyliusAkeneoPlugin\Command\Context\CommandContextInterface;
use Synolia\SyliusAkeneoPlugin\Message\Batch\BatchMessageInterface;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class AssetFamilyPayload extends AbstractPayload
{
    public const TEMP_AKENEO_TABLE_NAME = 'tmp_akeneo_assets';

    public const BATCH_COMMAND_NAME = 'akeneo:batch:assets';

    private ?\Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface $resources = null;

    public function __construct(
        AkeneoPimClientInterface $akeneoPimClient,
        ?CommandContextInterface $commandContext = null,
    ) {
        parent::__construct($akeneoPimClient, $commandContext);

        $this->setTmpTableName(self::TEMP_AKENEO_TABLE_NAME);
        $this->setCommandName(self::BATCH_COMMAND_NAME);
    }

    public function getResources(): ?ResourceCursorInterface
    {
        return $this->resources;
    }

    public function setResources(ResourceCursorInterface $resources): void
    {
        $this->resources = $resources;
    }

    public function createBatchMessage(array $items): BatchMessageInterface
    {
        throw new \InvalidArgumentException();
    }
}
