<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * @deprecated
 */
interface ApiConfigurationInterface extends ResourceInterface
{
    public function getBaseUrl(): ?string;

    public function setBaseUrl(string $baseUrl): self;

    public function getApiClientId(): ?string;

    public function setApiClientId(string $apiClientId): self;

    public function getApiClientSecret(): ?string;

    public function setApiClientSecret(string $apiClientSecret): self;

    public function getEdition(): string;

    public function setEdition(string $edition): self;

    public function getPaginationSize(): int;

    public function setPaginationSize(int $paginationSize): self;

    public function getUsername(): ?string;

    public function setUsername(string $username): self;

    public function getPassword(): ?string;

    public function setPassword(string $password): self;
}
