<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Synolia\SyliusAkeneoPlugin\Config\AkeneoEditionEnum;

/**
 * @deprecated To be removed in 4.0.
 * @ORM\Entity()
 * @ORM\Table("akeneo_api_configuration")
 */
class ApiConfiguration implements ApiConfigurationInterface
{
    public const MIN_AKENEO_PAGINATION_SIZE = 1;

    public const MAX_AKENEO_PAGINATION_SIZE = 100;

    public const DEFAULT_PAGINATION_SIZE = 100;

    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    private $baseUrl;

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    private $apiClientId;

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    private $apiClientSecret;

    /**
     * @var int
     * @ORM\Column(type="integer")
     * @Assert\Range(
     *      min = ApiConfiguration::MIN_AKENEO_PAGINATION_SIZE,
     *      max = ApiConfiguration::MAX_AKENEO_PAGINATION_SIZE,
     * )
     */
    private $paginationSize = self::DEFAULT_PAGINATION_SIZE;

    /**
     * @var string
     * @ORM\Column(type="string", options={"default" : "ce"})
     */
    private $edition = AkeneoEditionEnum::COMMUNITY;

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    private $username;

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    private $password;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    public function setBaseUrl(string $baseUrl): ApiConfigurationInterface
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    public function getApiClientId(): ?string
    {
        return $this->apiClientId;
    }

    public function setApiClientId(string $apiClientId): ApiConfigurationInterface
    {
        $this->apiClientId = $apiClientId;

        return $this;
    }

    public function getApiClientSecret(): ?string
    {
        return $this->apiClientSecret;
    }

    public function setApiClientSecret(string $apiClientSecret): ApiConfigurationInterface
    {
        $this->apiClientSecret = $apiClientSecret;

        return $this;
    }

    public function getEdition(): string
    {
        return $this->edition;
    }

    public function setEdition(string $edition): ApiConfigurationInterface
    {
        $this->edition = $edition;

        return $this;
    }

    public function getPaginationSize(): int
    {
        return $this->paginationSize;
    }

    public function setPaginationSize(int $paginationSize): ApiConfigurationInterface
    {
        $this->paginationSize = $paginationSize;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): ApiConfigurationInterface
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): ApiConfigurationInterface
    {
        $this->password = $password;

        return $this;
    }
}
