<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->import(dirname(__DIR__) . '/vendor/sylius-labs/coding-standard/ecs.php');

    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [
        dirname(__DIR__, 1) . '/src',
        dirname(__DIR__, 1) . '/tests/PHPUnit',
    ]);

    $parameters->set(Option::SKIP, [
        \PhpCsFixer\Fixer\Phpdoc\PhpdocTagTypeFixer::class,
        // @TODO remove this line when we drop php7.4 support
        \PhpCsFixer\Fixer\FunctionNotation\FunctionTypehintSpaceFixer::class
    ]);
};
