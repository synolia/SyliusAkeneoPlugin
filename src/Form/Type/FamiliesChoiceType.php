<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;

final class FamiliesChoiceType extends AbstractType
{
    public function __construct(private ClientFactoryInterface $clientFactory)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $familiesResult = $this->clientFactory->createFromApiCredentials()->getFamilyApi()->all();
        if (!$familiesResult->valid()) {
            return;
        }

        $families = [];
        foreach ($familiesResult as $family) {
            $families[$family['code']] = $family['code'];
        }

        $resolver->setDefaults([
            'multiple' => true,
            'choices' => $families,
            'required' => false,
        ]);
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
