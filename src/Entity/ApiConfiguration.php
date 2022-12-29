<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Synolia\SyliusAkeneoPlugin\Config\AkeneoEditionEnum;

/**
 * @ORM\Entity()
 * @ORM\Table("akeneo_api_configuration")
 */
class ApiConfiguration implements ResourceInterface
{
    public const MIN_AKENEO_PAGINATION_SIZE = 1;

    public const MAX_AKENEO_PAGINATION_SIZE = 100;

    public const DEFAULT_PAGINATION_SIZE = 100;

    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    private ?string $baseUrl = null;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    private ?string $apiClientId = null;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    private ?string $apiClientSecret = null;

    /**
     * @ORM\Column(type="integer")
     * @Assert\Range(
     *      min = ApiConfiguration::MIN_AKENEO_PAGINATION_SIZE,
     *      max = ApiConfiguration::MAX_AKENEO_PAGINATION_SIZE,
     * )
     */
    private int $paginationSize = self::DEFAULT_PAGINATION_SIZE;

    /** @ORM\Column(type="string", options={"default" : "ce"}) */
    private string $edition = AkeneoEditionEnum::COMMUNITY;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    private ?string $username = null;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    private ?string $password = null;

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

    /** @deprecated */
    public function isEnterprise(): ?bool
    {
        return $this->getEdition() === AkeneoEditionEnum::ENTERPRISE;
    }

    /** @deprecated Use setEdition */
    public function setIsEnterprise(bool $isEnterprise): self
    {
        @trigger_error('Method ' . __METHOD__ . ' is deprecated. Use setEdition() instead.', \E_USER_DEPRECATED);

        if ($isEnterprise) {
            $this->setEdition(AkeneoEditionEnum::ENTERPRISE);

            return $this;
        }

        $this->setEdition(AkeneoEditionEnum::COMMUNITY);

        return $this;
    }

    public function getEdition(): string
    {
        return $this->edition;
    }

    public function setEdition(string $edition): self
    {
        if (!\in_array($edition, AkeneoEditionEnum::getEditions(), true)) {
            throw new \InvalidArgumentException(\sprintf(
                'Akeneo edition "%s" is not valid.',
                $edition,
            ));
        }

        $this->edition = $edition;

        return $this;
    }

    public function getPaginationSize(): int
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
}
