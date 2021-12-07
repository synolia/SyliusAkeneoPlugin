<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Association;

use Akeneo\Pim\ApiClient\Pagination\Page;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Synolia\SyliusAkeneoPlugin\Command\Context\CommandContextInterface;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class AssociationTypePayload extends AbstractPayload
{
    public const TEMP_AKENEO_TABLE_NAME = 'tmp_akeneo_association_types';

    public const BATCH_COMMAND_NAME = 'akeneo:batch:association-types';

    public function __construct(
        AkeneoPimEnterpriseClientInterface $akeneoPimClient,
        ?CommandContextInterface $commandContext = null
    ) {
        parent::__construct($akeneoPimClient, $commandContext);

        $this->setTmpTableName(self::TEMP_AKENEO_TABLE_NAME);
        $this->setCommandName(self::BATCH_COMMAND_NAME);
    }

    private ?ResourceCursorInterface $resources;

    /** @return Page|ResourceCursorInterface|null */
    public function getResources()
    {
        return $this->resources;
    }

    /** @param mixed $resources */
    public function setResources($resources): void
    {
        $this->resources = $resources;
    }
}
