<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Service;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

final class SyliusAkeneoLocaleCodeProvider
{
    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $channelRepository;

    /** @var \Akeneo\Pim\ApiClient\AkeneoPimClientInterface */
    private $akeneoPimClient;

    public function __construct(AkeneoPimClientInterface $akeneoPimClient, RepositoryInterface $channelRepository)
    {
        $this->akeneoPimClient = $akeneoPimClient;
        $this->channelRepository = $channelRepository;
    }

    public function getUsedLocalesOnBothPlatforms(): array
    {
        $localesCode = [];
        foreach ($this->getUsedLocalesOnAkeneo() as $apiLocale) {
            if ($apiLocale['enabled'] === false || !in_array($apiLocale['code'], $this->getUsedLocalesOnSylius(), true)) {
                continue;
            }
            $localesCode[] = $apiLocale['code'];
        }

        return $localesCode;
    }

    private function getUsedLocalesOnAkeneo(): ResourceCursorInterface
    {
        return $this->akeneoPimClient->getLocaleApi()->all();
    }

    private function getUsedLocalesOnSylius(): array
    {
        $locales = [];
        /** @var \Sylius\Component\Core\Model\ChannelInterface $channel */
        foreach ($this->channelRepository->findAll() as $channel) {
            $locales = \array_unique(\array_merge($locales, $channel
                ->getLocales()
                ->map(function (LocaleInterface $locale) {
                    return (string) $locale->getCode();
                })
                ->toArray()))
            ;
        }

        return $locales;
    }
}
