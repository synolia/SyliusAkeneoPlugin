<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;

final class LocalesChoiceType extends AbstractType
{
    public function __construct(private SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $usedLocalesOnBothPlatforms = $this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms();

        $usedLocalesOnBothPlatforms = array_map(function ($locale) {
            return $this->syliusAkeneoLocaleCodeProvider->getAkeneoLocale($locale);
        }, $usedLocalesOnBothPlatforms);

        $resolver->setDefaults([
            'choices' => array_combine($usedLocalesOnBothPlatforms, $usedLocalesOnBothPlatforms),
            'multiple' => true,
            'required' => false,
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
