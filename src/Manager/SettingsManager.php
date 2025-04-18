<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Synolia\SyliusAkeneoPlugin\Entity\Setting;

final class SettingsManager implements SettingsManagerInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
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

        return json_decode((string) $setting->getValue(), true, 512, \JSON_THROW_ON_ERROR);
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

        $setting->setValue(json_encode($value, \JSON_THROW_ON_ERROR));
        $this->entityManager->flush($setting);

        return $this;
    }

    public function clear(string $name): SettingsManagerInterface
    {
        $this->set($name, null);

        return $this;
    }
}
