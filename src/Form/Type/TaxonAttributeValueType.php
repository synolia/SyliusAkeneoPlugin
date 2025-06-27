<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Sylius\Bundle\LocaleBundle\Form\Type\LocaleChoiceType;
use Sylius\Bundle\ResourceBundle\Form\DataTransformer\ResourceToIdentifierTransformer;
use Sylius\Bundle\ResourceBundle\Form\Registry\FormTypeRegistryInterface;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ReversedTransformer;
use Synolia\SyliusAkeneoPlugin\Entity\TaxonAttributeInterface;
use Synolia\SyliusAkeneoPlugin\Entity\TaxonAttributeValue;

#[AutoconfigureTag('form.type')]
class TaxonAttributeValueType extends AbstractResourceType
{
    public function __construct(
        #[Autowire(TaxonAttributeValue::class)]
        string $dataClass,
        #[Autowire('%synolia_sylius_akeneo.form.type.taxon_attribute_value.validation_groups%')]
        array $validationGroups,
        #[Autowire(TaxonAttributeChoiceType::class)]
        protected string $attributeChoiceType,
        protected RepositoryInterface $taxonAttributeRepository,
        #[Autowire('@sylius.repository.locale')]
        protected RepositoryInterface $localeRepository,
        #[Autowire('@sylius.form_registry.taxon_attribute_type')]
        protected FormTypeRegistryInterface $formTypeRegistry,
    ) {
        parent::__construct($dataClass, $validationGroups);
    }

    /** @inheritDoc */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('localeCode', LocaleChoiceType::class)
            ->add('attribute', $this->attributeChoiceType)
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
                $attributeValue = $event->getData();

                if (!$attributeValue instanceof TaxonAttributeValue) {
                    return;
                }

                $attribute = $attributeValue->getAttribute();
                if (!$attribute instanceof \Synolia\SyliusAkeneoPlugin\Entity\TaxonAttributeInterface) {
                    return;
                }

                $localeCode = $attributeValue->getLocaleCode();

                $this->addValueField($event->getForm(), $attribute, $localeCode);
            })
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
                /** @var array $attributeValue */
                $attributeValue = $event->getData();

                if (!isset($attributeValue['attribute'])) {
                    return;
                }

                $attribute = $this->taxonAttributeRepository->findOneBy(['code' => $attributeValue['attribute']]);
                if (!$attribute instanceof AttributeInterface) {
                    return;
                }

                $this->addValueField($event->getForm(), $attribute);
            })
        ;

        $builder->get('localeCode')->addModelTransformer(
            new ReversedTransformer(new ResourceToIdentifierTransformer($this->localeRepository, 'code')),
        );
    }

    protected function addValueField(
        FormInterface $form,
        AttributeInterface|TaxonAttributeInterface $attribute,
        ?string $localeCode = null,
    ): void {
        /** @phpstan-ignore-next-line */
        $form->add('value', $this->formTypeRegistry->get($attribute->getType(), 'default'), [
            'auto_initialize' => false,
            'configuration' => $attribute->getConfiguration(),
            'label' => $attribute->getName(),
            'locale_code' => $localeCode,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'sylius_taxon_attribute_value';
    }
}
