<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactory;

final class FamiliesChoiceType extends AbstractType
{
    /** @var AkeneoPimEnterpriseClientInterface */
    private $akeneoPimClient;

    public function __construct(ClientFactory $clientFactory)
    {
        $this->akeneoPimClient = $clientFactory->createFromApiCredentials();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $familiesResult = $this->akeneoPimClient->getFamilyApi()->all();
        if (empty($familiesResult)) {
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

    public function getParent()
    {
        return ChoiceType::class;
    }
}
