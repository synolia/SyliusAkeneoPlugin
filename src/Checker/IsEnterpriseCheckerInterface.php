<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Checker;

/**
 * @deprecated To be removed in 4.0. Use \Synolia\SyliusAkeneoPlugin\Retriever\EditionRetrieverInterface instead
 */
interface IsEnterpriseCheckerInterface
{
    public function isEnterprise(): bool;
}
