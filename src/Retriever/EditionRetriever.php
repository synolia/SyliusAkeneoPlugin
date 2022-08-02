<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Retriever;

use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Exceptions\ApiNotConfiguredException;

final class EditionRetriever implements EditionRetrieverInterface
{
    private RepositoryInterface $apiConfigurationRepository;

    private ?ApiConfiguration $configuration = null;

    public function __construct(RepositoryInterface $apiConfigurationRepository)
    {
        $this->apiConfigurationRepository = $apiConfigurationRepository;
    }

    /**
     * @throws ApiNotConfiguredException
     */
    public function getEdition(): string
    {
        if (null === $this->configuration) {
            /** @phpstan-ignore-next-line  */
            $this->configuration = $this->apiConfigurationRepository->findOneBy([], ['id' => 'DESC']);

            if (!$this->configuration instanceof ApiConfiguration) {
                throw new ApiNotConfiguredException();
            }
        }

        return $this->configuration->getEdition();
    }
}
