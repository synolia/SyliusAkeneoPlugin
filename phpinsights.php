<?php

declare(strict_types=1);

return [

    'preset' => 'symfony',

    'ide' => 'phpstorm',

    'exclude' => [
        'src/Migrations',
    ],

    'remove' => [

        // Sylius entities use setters
        NunoMaduro\PhpInsights\Domain\Sniffs\ForbiddenSetterSniff::class,
        // Sylius use suffix for interface
        SlevomatCodingStandard\Sniffs\Classes\SuperfluousInterfaceNamingSniff::class,
        // Sylius use suffix for exception
        SlevomatCodingStandard\Sniffs\Classes\SuperfluousExceptionNamingSniff::class,
        // Sylius use suffix for abstract
        SlevomatCodingStandard\Sniffs\Classes\SuperfluousAbstractClassNamingSniff::class,
        // incompatible with Sylius coding standard
        PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\SpaceAfterNotSniff::class,
    ],

    'config' => [
        NunoMaduro\PhpInsights\Domain\Insights\CyclomaticComplexityIsHigh::class => [
            'maxComplexity' => 30,
        ],
        ObjectCalisthenics\Sniffs\Files\FunctionLengthSniff::class => [
            'maxLength' => 50,
        ],
        ObjectCalisthenics\Sniffs\NamingConventions\ElementNameMinimalLengthSniff::class => [
            'minLength' => 3,
            'allowedShortNames' => ['id', 'io', 'up'],
        ],
        SlevomatCodingStandard\Sniffs\Functions\UnusedParameterSniff::class => [
            'exclude' => [
                '*Type.php',
            ],
        ],
        PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff::class => [
            'lineLimit' => 120,
            'absoluteLineLimit' => 140,
            'ignoreComments' => false,
        ],
        ObjectCalisthenics\Sniffs\Metrics\MaxNestingLevelSniff::class => [
            'maxNestingLevel' => 3,
        ],
    ],

    'requirements' => [
        'min-quality' => 80,
        'min-complexity' => 80,
        'min-architecture' => 80,
        'min-style' => 80,
        'disable-security-check' => false,
    ],
];
