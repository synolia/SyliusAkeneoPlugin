<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Attribute;

use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class RetrieveAttributesTask implements AkeneoTaskInterface
{
    public function __construct(
        private LoggerInterface $akeneoLogger,
        private ApiConnectionProviderInterface $apiConnectionProvider,
    ) {
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->akeneoLogger->debug(self::class);
        $this->akeneoLogger->debug(Messages::retrieveFromAPI($payload->getType()));
        $resources = $payload->getAkeneoPimClient()->getAttributeApi()->all(
            $this->apiConnectionProvider->get()->getPaginationSize(),
        );

        $noCodeCount = 0;
        foreach ($resources as $resource) {
            if (empty($resource['code'])) {
                ++$noCodeCount;
            }
        }

        $this->akeneoLogger->info(Messages::totalToImport($payload->getType(), $resources->key()));
        if ($noCodeCount > 0) {
            $this->akeneoLogger->info(Messages::noCodeToImport($payload->getType(), $noCodeCount));
        }

        $payload = new AttributePayload($payload->getAkeneoPimClient());
        $payload->setResources($resources);

        return $payload;
    }
}
