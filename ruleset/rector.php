<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    ->withPaths([
        \dirname(__DIR__) . '/src',
        \dirname(__DIR__) . '/tests/PHPUnit',
    ])
    ->withSkipPath(
        \dirname(__DIR__) . '/src/Entity',
    )
    ->withPHPStanConfigs([__DIR__ . '/phpstan.neon'])
    ->withPhpSets(php82: true)
    ->withAttributesSets(symfony: true, doctrine: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        doctrineCodeQuality: true,
        symfonyConfigs: true,
    )
    ->withSkip([
        ReadOnlyPropertyRector::class,
    ])
    ->withTypeCoverageLevel(0)
    ->withSets([
        SymfonySetList::SYMFONY_60,
        SymfonySetList::SYMFONY_61,
        SymfonySetList::SYMFONY_62,
        SymfonySetList::SYMFONY_63,
        SymfonySetList::SYMFONY_64,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
    ]);