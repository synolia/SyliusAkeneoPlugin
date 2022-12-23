<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Model\Configuration;

interface ApiConnectionInterface
{
    public const DEFAULT_PAGINATION_SIZE = 100;

    public function getBaseUrl(): string;

    public function getUsername(): string;

    public function getPassword(): string;

    public function getApiClientId(): string;

    public function getApiClientSecret(): string;

    public function getEdition(): string;

    public function getAxeAsModel(): string;

    public function getPaginationSize(): int;
}
