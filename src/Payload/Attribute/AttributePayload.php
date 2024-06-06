<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Attribute;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Synolia\SyliusAkeneoPlugin\Command\Context\CommandContextInterface;
use Synolia\SyliusAkeneoPlugin\Message\Batch\AttributeBatchMessage;
use Synolia\SyliusAkeneoPlugin\Message\Batch\BatchMessageInterface;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class AttributePayload extends AbstractPayload
{
    public const TEMP_AKENEO_TABLE_NAME = 'tmp_akeneo_attributes';

    public const BATCH_COMMAND_NAME = 'akeneo:batch:attributes';

    public function __construct(
        AkeneoPimClientInterface $akeneoPimClient,
        ?CommandContextInterface $commandContext = null,
    ) {
        parent::__construct($akeneoPimClient, $commandContext);

        $this->setTmpTableName(self::TEMP_AKENEO_TABLE_NAME);
        $this->setCommandName(self::BATCH_COMMAND_NAME);
    }

    private ResourceCursorInterface $resources;

    public function getResources(): ResourceCursorInterface
    {
        return $this->resources;
    }

    public function setResources(ResourceCursorInterface $resources): void
    {
        $this->resources = $resources;
    }

    public function createBatchMessage(array $items): BatchMessageInterface
    {
        return new AttributeBatchMessage($items);
    }
}
