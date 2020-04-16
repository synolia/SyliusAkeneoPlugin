<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ProductModel;

use Psr\Log\LoggerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Filter\ProductFilter;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class RetrieveProductModelsTask implements AkeneoTaskInterface
{
    /** @var EntityRepository */
    private $apiConfigurationRepository;

    /** @var ProductFilter */
    private $productFilter;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        EntityRepository $apiConfigurationRepository,
        ProductFilter $productFilter,
        LoggerInterface $logger
    ) {
        $this->apiConfigurationRepository = $apiConfigurationRepository;
        $this->productFilter = $productFilter;
        $this->logger = $logger;
    }

    /**
     * @param ProductModelPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        /** @var ApiConfiguration $apiConfiguration */
        $apiConfiguration = $this->apiConfigurationRepository->findOneBy([]);

        $queryParameters = $this->productFilter->getProductModelFilters($payload);

        $this->logger->debug(self::class);
        $this->logger->notice(Messages::retrieveFromAPI($payload->getType()));
        $resources = $payload->getAkeneoPimClient()->getProductModelApi()->all(
            $apiConfiguration->getPaginationSize() ?? 0,
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
