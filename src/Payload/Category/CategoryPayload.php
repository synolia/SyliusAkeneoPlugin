<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Category;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Synolia\SyliusAkeneoPlugin\Command\Context\CommandContextInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoCategoryResourcesException;
use Synolia\SyliusAkeneoPlugin\Message\Batch\BatchMessageInterface;
use Synolia\SyliusAkeneoPlugin\Message\Batch\CategoryBatchMessage;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class CategoryPayload extends AbstractPayload
{
    public const TEMP_AKENEO_TABLE_NAME = 'tmp_akeneo_categories';

    public const BATCH_COMMAND_NAME = 'akeneo:batch:categories';

    public function __construct(
        AkeneoPimClientInterface $akeneoPimClient,
        ?CommandContextInterface $commandContext = null,
    ) {
        parent::__construct($akeneoPimClient, $commandContext);

        $this->setTmpTableName(self::TEMP_AKENEO_TABLE_NAME);
        $this->setCommandName(self::BATCH_COMMAND_NAME);
    }

    private array $resources;

    public function getResources(): array
    {
        if (!isset($this->resources)) {
            throw new NoCategoryResourcesException('No resource found.');
        }

        return $this->resources;
    }

    public function setResources(array $resources): void
    {
        $this->resources = $resources;
    }

    public function createBatchMessage(array $items): BatchMessageInterface
    {
        return new CategoryBatchMessage($items);
    }
}
