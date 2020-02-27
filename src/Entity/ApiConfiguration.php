<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * @ORM\Entity()
 * @ORM\Table("akeneo_api_configuration")
 */
final class ApiConfiguration implements ResourceInterface
{
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
     */
    private $baseUrl;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $apiClientId;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $apiClientSecret;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $token;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $refreshToken;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $paginationSize;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $isEnterprise;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @ORM\OneToMany(
     *     targetEntity="Synolia\SyliusAkeneoPlugin\Entity\ApiConfigurationWebsiteMapping",
     *     mappedBy="apiConfiguration",
     *     cascade={"persist"},
     *     orphanRemoval=true
     * )
     */
    private $websiteMappings;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $username;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $password;

    public function __construct()
    {
        $this->websiteMappings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    public function setBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    public function getApiClientId(): ?string
    {
        return $this->apiClientId;
    }

    public function setApiClientId(string $apiClientId): self
    {
        $this->apiClientId = $apiClientId;

        return $this;
    }

    public function getApiClientSecret(): ?string
    {
        return $this->apiClientSecret;
    }

    public function setApiClientSecret(string $apiClientSecret): self
    {
        $this->apiClientSecret = $apiClientSecret;

        return $this;
    }

    public function isEnterprise(): ?bool
    {
        return $this->isEnterprise;
    }

    public function setIsEnterprise(bool $isEnterprise): self
    {
        $this->isEnterprise = $isEnterprise;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken): self
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function getPaginationSize(): ?int
    {
        return $this->paginationSize;
    }

    public function setPaginationSize(int $paginationSize): self
    {
        $this->paginationSize = $paginationSize;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getWebsiteMappings(): Collection
    {
        return $this->websiteMappings;
    }

    public function addWebsiteMapping(ApiConfigurationWebsiteMapping $websiteMapping): self
    {
        $websiteMapping->setApiConfiguration($this);
        $this->websiteMappings->add($websiteMapping);

        return $this;
    }

    public function removeWebsiteMapping(ApiConfigurationWebsiteMapping $websiteMapping): self
    {
        $websiteMapping->setApiConfiguration(null);
        $this->websiteMappings->removeElement($websiteMapping);

        return $this;
    }

    public function setWebsiteMappings(Collection $websiteMappings): self
    {
        $this->websiteMappings = $websiteMappings;

        return $this;
    }
}
