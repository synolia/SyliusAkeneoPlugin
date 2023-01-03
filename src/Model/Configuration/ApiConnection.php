<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Model\Configuration;

use Synolia\SyliusAkeneoPlugin\Config\AkeneoAxesEnum;
use Synolia\SyliusAkeneoPlugin\Config\AkeneoEditionEnum;

class ApiConnection implements ApiConnectionInterface
{
    public function __construct(private string $baseUrl, private string $username, private string $password, private string $apiClientId, private string $apiClientSecret, private string $edition = AkeneoEditionEnum::COMMUNITY, private string $axeAsModel = AkeneoAxesEnum::FIRST, private int $paginationSize = self::DEFAULT_PAGINATION_SIZE)
    {
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getApiClientId(): string
    {
        return $this->apiClientId;
    }

    public function getApiClientSecret(): string
    {
        return $this->apiClientSecret;
    }

    public function getEdition(): string
    {
        return $this->edition;
    }

    public function getAxeAsModel(): string
    {
        return $this->axeAsModel;
    }

    public function getPaginationSize(): int
    {
        return $this->paginationSize;
    }
}
