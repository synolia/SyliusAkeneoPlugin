<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Model;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

final class SettingType
{
    public const AKENEO_SETTINGS = [
        'import_referential_attributes' => [
            'type' => CheckboxType::class,
            'options' => [
                'required' => true,
            ],
        ],
    ];
}
