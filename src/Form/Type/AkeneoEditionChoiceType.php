<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Synolia\SyliusAkeneoPlugin\Config\AkeneoEditionEnum;

class AkeneoEditionChoiceType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => $this->getChoices(),
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    /**
     * @return array<string, string>
     */
    private function getChoices(): array
    {
        $choices = [];

        foreach ((new AkeneoEditionEnum())->getEditions() as $edition) {
            $choices['sylius.ui.admin.akeneo.editions.' . $edition] = $edition;
        }

        return $choices;
    }
}
