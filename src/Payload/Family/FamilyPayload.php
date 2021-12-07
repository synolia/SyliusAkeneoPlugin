<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Family;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Synolia\SyliusAkeneoPlugin\Command\Context\CommandContextInterface;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class FamilyPayload extends AbstractPayload
{
    public const TEMP_AKENEO_TABLE_NAME = 'tmp_akeneo_families';

    public const BATCH_COMMAND_NAME = 'akeneo:batch:families';

    private ResourceCursorInterface $resources;

    public function __construct(
        AkeneoPimEnterpriseClientInterface $akeneoPimClient,
        ?CommandContextInterface $commandContext = null
    ) {
        parent::__construct($akeneoPimClient, $commandContext);

        $this->setTmpTableName(self::TEMP_AKENEO_TABLE_NAME);
        $this->setCommandName(self::BATCH_COMMAND_NAME);
    }

    public function getResources(): ResourceCursorInterface
    {
        return $this->resources;
    }

    public function setResources(ResourceCursorInterface $resources): void
    {
        $this->resources = $resources;
    }
}
