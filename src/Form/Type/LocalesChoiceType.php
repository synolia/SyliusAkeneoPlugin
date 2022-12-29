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
        $resolver->setDefaults([
            'choices' => $this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms(),
            'multiple' => true,
            'required' => false,
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
