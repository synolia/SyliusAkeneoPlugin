<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Checker;

use Synolia\SyliusAkeneoPlugin\Config\AkeneoEditionEnum;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;

/**
 * @deprecated To be removed in 4.0. Use Synolia\SyliusAkeneoPlugin\Checker\EditionCheckerInterface::isEnterprise() instead.
 */
final class IsEnterpriseChecker implements IsEnterpriseCheckerInterface
{
    private ApiConnectionProviderInterface $apiConnectionProvider;

    public function __construct(ApiConnectionProviderInterface $apiConnectionProvider)
    {
        $this->apiConnectionProvider = $apiConnectionProvider;
    }

    public function isEnterprise(): bool
    {
        @trigger_error('Method ' . __METHOD__ . ' is deprecated', \E_USER_DEPRECATED);

        return $this->apiConnectionProvider->get()->getEdition() === AkeneoEditionEnum::ENTERPRISE;
    }
}
