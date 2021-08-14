<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\ProductModel;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Synolia\SyliusAkeneoPlugin\Command\Context\CommandContextInterface;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class ProductModelPayload extends AbstractPayload
{
    public const TEMP_AKENEO_TABLE_NAME = 'tmp_akeneo_product_models';

    public const BATCH_COMMAND_NAME = 'akeneo:batch:product-models';

    /** @var \Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface|null */
    private $resources;

    /** @var \Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface|null */
    private $modelResources;

    public function __construct(
        AkeneoPimEnterpriseClientInterface $akeneoPimClient,
        ?CommandContextInterface $commandContext = null
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

    public function getModelResources(): ?ResourceCursorInterface
    {
        return $this->modelResources;
    }

    public function setModelResources(?ResourceCursorInterface $modelResources): self
    {
        $this->modelResources = $modelResources;

        return $this;
    }
}
