<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\AssociationType;

use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Association\AssociationTypePayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Filter\SearchFilterProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Handler\Task\TaskHandlerProviderInterface;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Task\TaskHandlerTrait;

final class ProcessAssociationTypeTask implements AkeneoTaskInterface
{
    use TaskHandlerTrait {
        TaskHandlerTrait::__construct as private __taskHandlerConstruct;
    }

    public function __construct(
        private ApiConnectionProviderInterface $apiConnectionProvider,
        private LoggerInterface $akeneoLogger,
        private SearchFilterProviderInterface $searchFilterProvider,
        private TaskHandlerProviderInterface $taskHandlerProvider,
    ) {
        $this->__taskHandlerConstruct($taskHandlerProvider);
    }

    /**
     * @param AssociationTypePayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->akeneoLogger->debug(self::class);

        if ($payload->isContinue()) {
            $this->continue($payload);

            return $payload;
        }

        $page = $payload->getAkeneoPimClient()->getAssociationTypeApi()->listPerPage(
            $this->apiConnectionProvider->get()->getPaginationSize(),
            false,
            $this->searchFilterProvider->get($payload),
        );

        $this->handle($payload, $page);

        return $payload;
    }
}
