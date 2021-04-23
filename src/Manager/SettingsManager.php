<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Synolia\SyliusAkeneoPlugin\Entity\Setting;

final class SettingsManager implements SettingsManagerInterface
{
    /** @var \Doctrine\ORM\EntityManager */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        \assert($entityManager instanceof EntityManager);
        $this->entityManager = $entityManager;
    }

    /**
     * Return setting value by its name.
     *
     * @param mixed|null $default Value to return if the setting is not set
     *
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        /** @var Setting $setting */
        $setting = $this->entityManager->getRepository(Setting::class)->findOneBy(['name' => $name]);
        if (!$setting instanceof Setting) {
            return $default;
        }

        return \json_decode($setting->getValue(), true);
    }

    /**
     * @param mixed $value
     */
    public function set(string $name, $value): SettingsManagerInterface
    {
        /** @var Setting $setting */
        $setting = $this->entityManager->getRepository(Setting::class)->findOneBy(['name' => $name]);
        if (!$setting instanceof Setting) {
            $setting = new Setting($name);
            $this->entityManager->persist($setting);
        }

        $setting->setValue(\json_encode($value));
        $this->entityManager->flush($setting);

        return $this;
    }

    public function clear(string $name): SettingsManagerInterface
    {
        $this->set($name, null);

        return $this;
    }
}
