<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Association;

use Akeneo\Pim\ApiClient\Pagination\Page;
use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Association\AssociationTypePayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

class RetrieveAssociationTask implements AkeneoTaskInterface
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload instanceof AssociationTypePayload) {
            return $payload;
        }

        $this->logger->debug(self::class);
        $this->logger->notice(Messages::retrieveFromAPI($payload->getType()));

        $resources = $payload->getAkeneoPimClient()->getAssociationTypeApi()->all();

        if (!$resources instanceof Page) {
            return $payload;
        }

        $payload->setResources($resources);

        $this->logger->info(Messages::totalToImport($payload->getType(), $resources->getCount()));

        return $payload;
    }
}