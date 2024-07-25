<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ProductModel;

use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Filter\SearchFilterProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Handler\Task\TaskHandlerProviderInterface;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Task\TaskHandlerTrait;
use Throwable;

final class ProcessProductModelsTask implements AkeneoTaskInterface
{
    use TaskHandlerTrait{
        TaskHandlerTrait::__construct as private __taskHandlerConstruct;
    }

    public function __construct(
        private ApiConnectionProviderInterface $apiConnectionProvider,
        private LoggerInterface $logger,
        private SearchFilterProviderInterface $searchFilterProvider,
        TaskHandlerProviderInterface $taskHandlerProvider,
    ) {
        $this->__taskHandlerConstruct($taskHandlerProvider);
    }

    /**
     * @param ProductModelPayload $payload
     *
     * @throws Throwable
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);

        if ($payload->isContinue()) {
            $this->continue($payload);

            return $payload;
        }

        $this->logger->notice(Messages::retrieveFromAPI($payload->getType()));

        $resources = $payload->getAkeneoPimClient()->getProductModelApi()->all(
            $this->apiConnectionProvider->get()->getPaginationSize(),
            $this->searchFilterProvider->get($payload),
        );

        $this->handle($payload, $resources);

        return $payload;
    }
}
