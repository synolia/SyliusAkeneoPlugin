<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
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
            ->add('password', PasswordType::class)
            ->add('apiClientId', TextType::class)
            ->add('apiClientSecret', TextType::class)
            ->add('paginationSize', IntegerType::class)
            ->add('isEnterprise', CheckboxType::class, [
                'required' => false,
            ])
            ->add('channel', ApiConfigurationChannelType::class)
            ->add('testCredentials', SubmitType::class, [
                'attr' => ['class' => 'ui secondary button'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'sylius.ui.save',
                'attr' => ['class' => 'ui primary button'],
            ])
        ;
    }
}
