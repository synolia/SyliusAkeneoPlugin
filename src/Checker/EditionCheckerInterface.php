<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Checker;

interface EditionCheckerInterface
{
    public function isCommunityEdition(): bool;

    public function isGrowthEdition(): bool;

    public function isEnterprise(): bool;

    public function isSerenityEdition(): bool;
}
