<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Product;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Synolia\SyliusAkeneoPlugin\Command\Context\CommandContextInterface;
use Synolia\SyliusAkeneoPlugin\Message\Batch\BatchMessageInterface;
use Synolia\SyliusAkeneoPlugin\Message\Batch\ProductVariantBatchMessage;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class ProductPayload extends AbstractPayload
{
    public const TEMP_AKENEO_TABLE_NAME = 'tmp_akeneo_products';

    public const BATCH_COMMAND_NAME = 'akeneo:batch:products';

    private PageInterface $resources;

    public function __construct(
        AkeneoPimClientInterface $akeneoPimClient,
        ?CommandContextInterface $commandContext = null,
    ) {
        parent::__construct($akeneoPimClient, $commandContext);

        $this->setTmpTableName(self::TEMP_AKENEO_TABLE_NAME);
        $this->setCommandName(self::BATCH_COMMAND_NAME);
    }

    public function getResources(): PageInterface
    {
        return $this->resources;
    }

    public function setResources(PageInterface $resources): void
    {
        $this->resources = $resources;
    }

    public function createBatchMessage(array $items): BatchMessageInterface
    {
        return new ProductVariantBatchMessage($items);
    }
}
