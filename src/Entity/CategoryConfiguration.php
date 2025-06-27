<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;
use Synolia\SyliusAkeneoPlugin\Repository\CategoryConfigurationRepository;

#[ORM\Entity(repositoryClass: CategoryConfigurationRepository::class)]
#[ORM\Table(name: 'akeneo_api_configuration_categories')]
class CategoryConfiguration implements ResourceInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    #[ORM\Column(type: Types::ARRAY)]
    private array $notImportCategories = [];

    #[ORM\Column(type: Types::ARRAY)]
    private array $rootCategories = [];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $useAkeneoPositions = false;

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return array<string>
     */
    public function getNotImportCategories(): array
    {
        return $this->notImportCategories;
    }

    /**
     * @param array<string> $notImportCategories
     */
    public function setNotImportCategories(array $notImportCategories): self
    {
        $this->notImportCategories = $notImportCategories;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getRootCategories(): array
    {
        return $this->rootCategories;
    }

    /**
     * @param array<string> $rootCategories
     */
    public function setRootCategories(array $rootCategories): self
    {
        $this->rootCategories = $rootCategories;

        return $this;
    }

    public function useAkeneoPositions(): bool
    {
        return $this->useAkeneoPositions;
    }

    public function setUseAkeneoPositions(bool $useAkeneoPositions): self
    {
        $this->useAkeneoPositions = $useAkeneoPositions;

        return $this;
    }
}
