<?php

declare(strict_types=1);

return [

    'preset' => 'symfony',

    /*
    |--------------------------------------------------------------------------
    | IDE
    |--------------------------------------------------------------------------
    |
    | This options allow to add hyperlinks in your terminal to quickly open
    | files in your favorite IDE while browsing your PhpInsights report.
    |
    | Supported: "textmate", "macvim", "emacs", "sublime", "phpstorm",
    | "atom", "vscode".
    |
    | If you have another IDE that is not in this list but which provide an
    | url-handler, you could fill this config with a pattern like this:
    |
    | myide://open?url=file://%f&line=%l
    |
    */

    'ide' => 'phpstorm',

    'exclude' => [
        'src/Migrations'
    ],

    'remove' => [
        NunoMaduro\PhpInsights\Domain\Sniffs\ForbiddenSetterSniff::class
    ],

    'config' => [
        NunoMaduro\PhpInsights\Domain\Insights\CyclomaticComplexityIsHigh::class => [
            'maxComplexity' => 15,
        ]
    ],

    'requirements' => [
       'min-quality' => 80,
       'min-complexity' => 80,
       'min-architecture' => 80,
       'min-style' => 80,
       'disable-security-check' => false,
    ],

];
