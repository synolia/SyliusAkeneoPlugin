<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->import(dirname(__DIR__) . '/vendor/sylius-labs/coding-standard/ecs.php');

    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [
        dirname(__DIR__, 1) . '/src',
        dirname(__DIR__, 1) . '/tests/Behat',
        dirname(__DIR__, 1) . '/tests/PHPUnit',
        dirname(__DIR__, 1) . '/spec',
    ]);

    $parameters->set(Option::SETS, [
        SetList::SYMFONY,
        SetList::PHP_73_MIGRATION,
        SetList::PHP_CS_FIXER,
    ]);
    $parameters->set(Option::SKIP, [
        PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer::class => [
            dirname(__DIR__, 1) . '/src/*/*Configuration*.php',
        ]
    ]);
};
