<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractKernelTestCase extends KernelTestCase
{
    protected static function getContainer(): ContainerInterface
    {
        return static::$container;
    }
}
