<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Doctrine\Persistence\ObjectRepository;
use Sylius\Component\Locale\Model\LocaleInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class LocalesChoiceType extends AbstractType
{
    /** @var ObjectRepository */
    private $localeRepository;

    public function __construct(ObjectRepository $localeRepository)
    {
        $this->localeRepository = $localeRepository;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $locales = $this->localeRepository->findAll();

        if (empty($locales)) {
            return;
        }

        $localesCode = [];
        /** @var LocaleInterface $locale */
        foreach ($locales as $locale) {
            $localesCode[$locale->getCode()] = $locale->getCode();
        }

        $resolver->setDefaults(['choices' => $localesCode]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
