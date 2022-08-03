<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Checker;

/**
 * @deprecated To be removed in 4.0. Use Synolia\SyliusAkeneoPlugin\Checker\EditionCheckerInterface::isEnterprise() instead.
 */
final class IsEnterpriseChecker implements IsEnterpriseCheckerInterface
{
    private EditionCheckerInterface $editionChecker;

    public function __construct(EditionCheckerInterface $editionChecker)
    {
        $this->editionChecker = $editionChecker;
    }

    public function isEnterprise(): bool
    {
        @trigger_error('Method ' . __METHOD__ . ' is deprecated', \E_USER_DEPRECATED);

        return $this->editionChecker->isEnterprise();
    }
}
