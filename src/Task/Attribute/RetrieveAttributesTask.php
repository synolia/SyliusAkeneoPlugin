<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Attribute;

use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class RetrieveAttributesTask implements AkeneoTaskInterface
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);
        $this->logger->notice(Messages::retrieveFromAPI($payload->getType()));
        $resources = $payload->getAkeneoPimClient()->getAttributeApi()->all();

        $noCodeCount = 0;
        foreach ($resources as $resource) {
            if (empty($resource['code'])) {
                ++$noCodeCount;
            }
        }

        $this->logger->info(Messages::totalToImport($payload->getType(), $resources->key()));
        if ($noCodeCount > 0) {
            $this->logger->warning(Messages::noCodeToImport($payload->getType(), $noCodeCount));
        }

        $payload = new AttributePayload($payload->getAkeneoPimClient());
        $payload->setResources($resources);

        return $payload;
    }
}
