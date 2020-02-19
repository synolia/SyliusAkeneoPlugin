<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class ApiConfigurationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('baseUrl', TextType::class)
            ->add('username', TextType::class)
            ->add('password', TextType::class)
            ->add('apiClientId', TextType::class)
            ->add('apiClientSecret', TextType::class)
            ->add('paginationSize', IntegerType::class)
            ->add('isEnterprise', CheckboxType::class)

            ->add('websiteMappings', CollectionType::class, [
                'required' => false,
                'entry_type' => ApiConfigurationWebsiteMappingType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])

            ->add('submit', SubmitType::class)
        ;
    }
}
