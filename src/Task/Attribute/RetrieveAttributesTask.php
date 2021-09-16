<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Attribute;

use Akeneo\Pim\ApiClient\Pagination\Page;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\ConfigurationProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class RetrieveAttributesTask implements AkeneoTaskInterface
{
    /** @var LoggerInterface */
    private $logger;

    /** @var ConfigurationProvider */
    private $configurationProvider;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    public function __construct(
        LoggerInterface $akeneoLogger,
        ConfigurationProvider $configurationProvider,
        EntityManagerInterface $entityManager
    ) {
        $this->logger = $akeneoLogger;
        $this->configurationProvider = $configurationProvider;
        $this->entityManager = $entityManager;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);
        $this->logger->notice(Messages::retrieveFromAPI($payload->getType()));

        $resources = $payload->getAkeneoPimClient()->getAttributeApi()->listPerPage(
            $this->configurationProvider->getConfiguration()->getPaginationSize(),
            true,
        );

        $itemCount = 0;
        while (
            ($resources instanceof Page && $resources->hasNextPage()) ||
            ($resources instanceof Page && !$resources->hasPreviousPage()) ||
            $resources instanceof Page
        ) {
            foreach ($resources->getItems() as $item) {
                $sql = \sprintf(
                    'INSERT INTO `%s` (`values`) VALUES (:values);',
                    AttributePayload::TEMP_AKENEO_TABLE_NAME,
                );
                $stmt = $this->entityManager->getConnection()->prepare($sql);
                $stmt->bindValue('values', \json_encode($item));
                $stmt->execute();

                ++$itemCount;
            }

            $resources = $resources->getNextPage();
        }

        $this->logger->info(Messages::totalToImport($payload->getType(), $itemCount));

        return $payload;
    }
}
