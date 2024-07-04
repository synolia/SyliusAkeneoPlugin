<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Association;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Synolia\SyliusAkeneoPlugin\Command\Context\CommandContextInterface;
use Synolia\SyliusAkeneoPlugin\Message\Batch\AssociationTypeBatchMessage;
use Synolia\SyliusAkeneoPlugin\Message\Batch\BatchMessageInterface;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class AssociationTypePayload extends AbstractPayload
{
    public const TEMP_AKENEO_TABLE_NAME = 'tmp_akeneo_association_types';

    public const BATCH_COMMAND_NAME = 'akeneo:batch:association-types';

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
        return new AssociationTypeBatchMessage($items);
    }
}
