<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * @ORM\Entity(repositoryClass="CategoryConfigurationRepository")
 * @ORM\Table("akeneo_api_configuration_categories")
 */
final class CategoryConfiguration implements ResourceInterface
{
    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var array<string>
     * @ORM\Column(type="array")
     */
    private $notImportCategories = [];

    /**
     * @var array<string>
     * @ORM\Column(type="array")
     */
    private $rootCategories = [];

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
}
