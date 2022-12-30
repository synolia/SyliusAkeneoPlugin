<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\ProductModel;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Synolia\SyliusAkeneoPlugin\Command\Context\CommandContextInterface;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class ProductModelPayload extends AbstractPayload
{
    public const TEMP_AKENEO_TABLE_NAME = 'tmp_akeneo_product_models';

    public const BATCH_COMMAND_NAME = 'akeneo:batch:product-models';

    private ResourceCursorInterface $resources;

    public function __construct(
        AkeneoPimClientInterface $akeneoPimClient,
        ?CommandContextInterface $commandContext = null,
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
