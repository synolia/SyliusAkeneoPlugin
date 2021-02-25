<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Association;

use Akeneo\Pim\ApiClient\Pagination\Page;
use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Association\AssociationTypePayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\ConfigurationProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class RetrieveAssociationTask implements AkeneoTaskInterface
{
    /** @var ConfigurationProvider */
    private $configurationProvider;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(ConfigurationProvider $configurationProvider, LoggerInterface $logger)
    {
        $this->configurationProvider = $configurationProvider;
        $this->logger = $logger;
    }

    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload instanceof AssociationTypePayload) {
            return $payload;
        }

        $this->logger->debug(self::class);
        $this->logger->notice(Messages::retrieveFromAPI($payload->getType()));

        $resources = $payload->getAkeneoPimClient()->getAssociationTypeApi()->listPerPage(
            $this->configurationProvider->getConfiguration()->getPaginationSize()
        );

        if (!$resources instanceof Page) {
            return $payload;
        }

        $payload->setResources($resources);

        $this->logger->info(Messages::totalToImport($payload->getType(), count($resources->getItems())));

        return $payload;
    }
}
