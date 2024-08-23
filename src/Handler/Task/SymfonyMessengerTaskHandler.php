<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Handler\Task;

use Akeneo\Pim\ApiClient\Pagination\Page;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Synolia\SyliusAkeneoPlugin\Factory\Message\Batch\BatchMessageFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;

class SymfonyMessengerTaskHandler implements TaskHandlerInterface
{
    public const HANDLER_CODE = 'messenger';

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected LoggerInterface $logger,
        private MessageBusInterface $bus,
        private BatchMessageFactoryInterface $batchMessageFactory,
    ) {
    }

    public function support(PipelinePayloadInterface $pipelinePayload): bool
    {
        return $pipelinePayload->getHandler() === self::HANDLER_CODE;
    }

    public function batch(PipelinePayloadInterface $pipelinePayload, array $items): void
    {
        $this->bus->dispatch($this->batchMessageFactory->createFromPayload($pipelinePayload, $items));
    }

    public function handle(
        PipelinePayloadInterface $pipelinePayload,
        iterable|PageInterface $handleType,
    ): void {
        $count = 0;
        $items = [];

        if ($handleType instanceof PageInterface) {
            $this->handleByPage($pipelinePayload, $handleType, $count, $items);
        } else {
            $this->handleByCursor($pipelinePayload, $handleType, $count, $items);
        }
    }

    private function handleByPage(
        PipelinePayloadInterface $payload,
        PageInterface $page,
        int &$count = 0,
        array &$items = [],
    ): void {
        while (
            ($page instanceof Page && $page->hasNextPage()) ||
            ($page instanceof Page && !$page->hasPreviousPage()) ||
            $page instanceof Page
        ) {
            foreach ($page->getItems() as $item) {
                ++$count;
                $items[] = $item;
                $identifiers[] = $item['code'] ?? $item['identifier'];

                if (0 === $count % $payload->getBatchSize()) {
                    $this->logger->notice('Batching', ['codes' => $identifiers]);
                    $this->batch($payload, $items);
                    $items = [];
                    $identifiers = [];
                }
            }

            $page = $page->getNextPage();
        }

        if ($items !== []) {
            $this->batch($payload, $items);
            $items = [];
        }
    }

    private function handleByCursor(
        PipelinePayloadInterface $payload,
        iterable $resourceCursor,
        int &$count = 0,
        array &$items = [],
    ): void {
        /**
         * @var array<string, mixed> $item
         */
        foreach ($resourceCursor as $item) {
            ++$count;
            $items[] = $item;
            $identifiers[] = $item['code'] ?? $item['identifier'];

            if (0 === $count % $payload->getBatchSize()) {
                $this->logger->notice('Batching', ['codes' => $identifiers]);
                $this->batch($payload, $items);
                $items = [];
                $identifiers = [];
            }
        }

        if ($items !== []) {
            $this->batch($payload, $items);
            $items = [];
        }
    }

    public function setUp(PipelinePayloadInterface $pipelinePayload): PipelinePayloadInterface
    {
        return $pipelinePayload;
    }

    public function tearDown(PipelinePayloadInterface $pipelinePayload): PipelinePayloadInterface
    {
        return $pipelinePayload;
    }

    public function continue(PipelinePayloadInterface $pipelinePayload): void
    {
    }
}
