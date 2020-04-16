<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ProductModel;

use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Filter\ProductFilter;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Provider\ConfigurationProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class RetrieveProductModelsTask implements AkeneoTaskInterface
{
    /** @var ProductFilter */
    private $productFilter;

    /** @var LoggerInterface */
    private $logger;

    /** @var ConfigurationProvider */
    private $configurationProvider;

    public function __construct(
        ProductFilter $productFilter,
        ConfigurationProvider $configurationProvider,
        LoggerInterface $logger
    ) {
        $this->productFilter = $productFilter;
        $this->logger = $logger;
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * @param ProductModelPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $queryParameters = $this->productFilter->getProductModelFilters($payload);

        $this->logger->debug(self::class);
        $this->logger->notice(Messages::retrieveFromAPI($payload->getType()));
        $resources = $payload->getAkeneoPimClient()->getProductModelApi()->all(
            $this->configurationProvider->getConfiguration()->getPaginationSize(),
            ['search' => $queryParameters]
        );

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

        $payload = new ProductModelPayload($payload->getAkeneoPimClient());
        $payload->setResources($resources);

        return $payload;
    }
}
