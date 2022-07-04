<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Checker;

use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Config\AkeneoEditionEnum;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfigurationInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\ApiNotConfiguredException;

/**
 * @deprecated To be removed in 4.0.
 */
final class IsEnterpriseChecker implements IsEnterpriseCheckerInterface
{
    /** @var RepositoryInterface */
    private RepositoryInterface $apiConfigurationRepository;

    private ?ApiConfigurationInterface $configuration = null;

    public function __construct(RepositoryInterface $apiConfigurationRepository)
    {
        $this->apiConfigurationRepository = $apiConfigurationRepository;
    }

    public function isEnterprise(): bool
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);

        if (null === $this->configuration) {
            $this->configuration = $this->apiConfigurationRepository->findOneBy([]);

            if (!$this->configuration instanceof ApiConfiguration) {
                throw new ApiNotConfiguredException();
            }
        }

        return $this->configuration->getEdition() === AkeneoEditionEnum::ENTERPRISE;
    }
}
