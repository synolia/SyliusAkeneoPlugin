<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Checker;

use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Exceptions\ApiNotConfiguredException;

final class IsEnterpriseChecker implements IsEnterpriseCheckerInterface
{
    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $apiConfigurationRepository;

    public function __construct(RepositoryInterface $apiConfigurationRepository)
    {
        $this->apiConfigurationRepository = $apiConfigurationRepository;
    }

    public function isEnterprise(): bool
    {
        /** @var ApiConfiguration|null $apiConfiguration */
        $apiConfiguration = $this->apiConfigurationRepository->findOneBy([]);

        if (!$apiConfiguration instanceof ApiConfiguration) {
            throw new ApiNotConfiguredException();
        }

        return $apiConfiguration->isEnterprise() ?? false;
    }
}
