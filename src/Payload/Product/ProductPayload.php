<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Product;

use Akeneo\Pim\ApiClient\Pagination\Page;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Synolia\SyliusAkeneoPlugin\Command\Context\CommandContextInterface;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class ProductPayload extends AbstractPayload
{
    public const TEMP_AKENEO_TABLE_NAME = 'tmp_akeneo_products';

    public const BATCH_COMMAND_NAME = 'akeneo:batch:products';

    private Page $resources;

    public function __construct(AkeneoPimEnterpriseClientInterface $akeneoPimClient, ?CommandContextInterface $commandContext = null)
    {
        parent::__construct($akeneoPimClient, $commandContext);

        $this->setTmpTableName(self::TEMP_AKENEO_TABLE_NAME);
        $this->setCommandName(self::BATCH_COMMAND_NAME);
    }

    public function getResources(): Page
    {
        return $this->resources;
    }

    public function setResources(Page $resources): void
    {
        $this->resources = $resources;
    }
}
