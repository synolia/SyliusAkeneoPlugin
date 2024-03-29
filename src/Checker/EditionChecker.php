<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Checker;

use Synolia\SyliusAkeneoPlugin\Config\AkeneoEditionEnum;
use Synolia\SyliusAkeneoPlugin\Retriever\EditionRetrieverInterface;

final class EditionChecker implements EditionCheckerInterface
{
    public function __construct(private EditionRetrieverInterface $editionRetriever)
    {
    }

    public function isCommunityEdition(): bool
    {
        return $this->editionRetriever->getEdition() === AkeneoEditionEnum::COMMUNITY;
    }

    public function isGrowthEdition(): bool
    {
        return $this->editionRetriever->getEdition() === AkeneoEditionEnum::GROWTH;
    }

    public function isEnterprise(): bool
    {
        return $this->editionRetriever->getEdition() === AkeneoEditionEnum::ENTERPRISE;
    }

    public function isSerenityEdition(): bool
    {
        return $this->editionRetriever->getEdition() === AkeneoEditionEnum::SERENITY;
    }
}
