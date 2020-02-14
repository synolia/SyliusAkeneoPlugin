<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Synolia\SyliusAkeneoPlugin\Model\SettingType;

final class SettingsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($options['data'] as $name => $value) {
            $configuration = SettingType::AKENEO_SETTINGS[$name];
            // If setting's value exists in data and setting isn't disabled

            $fieldType = $configuration['type'];
            $fieldOptions = $configuration['options'];
            $fieldOptions['constraints'] = $configuration['constraints'] ?? [];

            // Validator constraints
            if (!empty($fieldOptions['constraints']) && \is_array($fieldOptions['constraints'])) {
                $constraints = [];
                foreach ($fieldOptions['constraints'] as $class => $constraintOptions) {
                    if (!\class_exists($class)) {
                        throw new \InvalidArgumentException(\sprintf('Constraint class "%s" not found', $class));
                    }
                    $constraints[] = new $class($constraintOptions);
                }

                $fieldOptions['constraints'] = $constraints;
            }

            // Label I18n
            $fieldOptions['label'] = 'sylius.ui.admin.akeneo.' . $name;
            $fieldOptions['translation_domain'] = 'messages';

            // Choices I18n
            if (!empty($fieldOptions['choices'])) {
                $fieldOptions['choices'] = \array_map(
                    static function ($label) use ($fieldOptions) {
                        return $fieldOptions['label'] . '_choices.' . $label;
                    },
                    \array_combine($fieldOptions['choices'], $fieldOptions['choices'])
                );
            }
            $builder->add($name, $fieldType, $fieldOptions);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'disabled_settings' => [],
            'data' => [],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'settings_management';
    }
}
