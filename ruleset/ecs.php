<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\FunctionNotation\FunctionTypehintSpaceFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTagTypeFixer;
use SlevomatCodingStandard\Sniffs\Classes\RequireMultiLineMethodSignatureSniff;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->import(dirname(__DIR__) . '/vendor/sylius-labs/coding-standard/ecs.php');

    $ecsConfig->paths([
        dirname(__DIR__, 1) . '/src',
        dirname(__DIR__, 1) . '/tests/PHPUnit',
    ]);

    $ecsConfig->skip([
        PhpdocTagTypeFixer::class,
        FunctionTypehintSpaceFixer::class
    ]);

    $ecsConfig->rule(RequireMultiLineMethodSignatureSniff::class);
};
