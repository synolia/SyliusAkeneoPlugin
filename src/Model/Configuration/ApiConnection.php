<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Model\Configuration;

use Synolia\SyliusAkeneoPlugin\Config\AkeneoAxesEnum;
use Synolia\SyliusAkeneoPlugin\Config\AkeneoEditionEnum;

class ApiConnection implements ApiConnectionInterface
{
    private string $baseUrl;

    private string $apiClientId;

    private string $apiClientSecret;

    private int $paginationSize;

    private string $edition;

    private string $axeAsModel;

    private string $username;

    private string $password;

    public function __construct(
        string $baseUrl,
        string $username,
        string $password,
        string $apiClientId,
        string $apiClientSecret,
        string $edition = AkeneoEditionEnum::COMMUNITY,
        string $axeAsModel = AkeneoAxesEnum::FIRST,
        int $paginationSize = self::DEFAULT_PAGINATION_SIZE
    ) {
        $this->baseUrl = $baseUrl;
        $this->username = $username;
        $this->password = $password;
        $this->apiClientId = $apiClientId;
        $this->apiClientSecret = $apiClientSecret;
        $this->edition = $edition;
        $this->axeAsModel = $axeAsModel;
        $this->paginationSize = $paginationSize;
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
