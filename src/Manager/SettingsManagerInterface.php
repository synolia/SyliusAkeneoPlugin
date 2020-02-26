<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Manager;

interface SettingsManagerInterface
{
    /**
     * Return setting value by its name
     *
     * @param mixed|null $default Value to return if the setting is not set
     *
     * @return mixed
     */
    public function get(string $name, $default = null);

    /**
     * @param mixed $value
     */
    public function set(string $name, $value): self;

    /**
     * Clears setting value
     */
    public function clear(string $name): self;
}
