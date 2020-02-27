<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;

final class ConfigurationProvider
{
    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getConfiguration(): ApiConfiguration
    {
        /** @var ApiConfiguration $apiConfiguration */
        $apiConfiguration = $this->entityManager->getRepository(ApiConfiguration::class)->findOneBy([]);

        if (!$apiConfiguration instanceof ApiConfiguration) {
            throw new \Exception('The API is not configured in the admin section.');
        }

        return $apiConfiguration;
    }
}
