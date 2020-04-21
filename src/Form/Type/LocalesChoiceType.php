<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Doctrine\Persistence\ObjectRepository;
use Sylius\Component\Locale\Model\LocaleInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactory;

final class LocalesChoiceType extends AbstractType
{
    /** @var ObjectRepository */
    private $localeRepository;

    /** @var AkeneoPimClientInterface */
    private $akeneoPimClient;

    public function __construct(ObjectRepository $localeRepository, ClientFactory $clientFactory)
    {
        $this->localeRepository = $localeRepository;
        $this->akeneoPimClient = $clientFactory->createFromApiCredentials();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resultLocales = $this->localeRepository->findAll();
        $localesApi = $this->akeneoPimClient->getLocaleApi()->all();
        if (empty($resultLocales) || empty($localesApi)) {
            return;
        }

        $locales = [];
        /** @var LocaleInterface $locale */
        foreach ($resultLocales as $locale) {
            $locales[] = $locale->getCode();
        }

        $localesCode = [];
        foreach ($localesApi as $locale) {
            if (!in_array($locale['code'], $locales) || $locale['enabled'] === false) {
                continue;
            }
            $localesCode[$locale['code']] = $locale['code'];
        }

        $resolver->setDefaults(['choices' => $localesCode, 'multiple' => true, 'required' => false]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
