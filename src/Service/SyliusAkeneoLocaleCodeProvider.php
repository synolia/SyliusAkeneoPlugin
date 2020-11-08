<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Service;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

final class SyliusAkeneoLocaleCodeProvider
{
    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $channelRepository;

    /** @var \Akeneo\Pim\ApiClient\AkeneoPimClientInterface */
    private $akeneoPimClient;

    /** @var array<string> */
    private $localesCode = [];

    public function __construct(AkeneoPimClientInterface $akeneoPimClient, RepositoryInterface $channelRepository)
    {
        $this->akeneoPimClient = $akeneoPimClient;
        $this->channelRepository = $channelRepository;
    }

    public function getUsedLocalesOnBothPlatforms(): array
    {
        if ($this->localesCode !== []) {
            return $this->localesCode;
        }

        foreach ($this->getUsedLocalesOnAkeneo() as $apiLocale) {
            if ($apiLocale['enabled'] === false || !in_array($apiLocale['code'], $this->getUsedLocalesOnSylius(), true)) {
                continue;
            }
            $this->localesCode[$apiLocale['code']] = $apiLocale['code'];
        }

        return $this->localesCode;
    }

    /**
     * @param array|string $data
     */
    public function isLocaleDataTranslation(AttributeInterface $attribute, $data, string $locale): bool
    {
        if (isset($attribute->getConfiguration()['choices'][$data]) && array_key_exists($locale, $attribute->getConfiguration()['choices'][$data])) {
            return true;
        }

        if (is_array($data) && $data['locale'] === $locale) {
            return true;
        }

        return false;
    }

    public function isActiveLocale(string $locale): bool
    {
        $locales = $this->getUsedLocalesOnBothPlatforms();

        return in_array($locale, $locales, true);
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
                ->map(static function (LocaleInterface $locale) {
                    return (string) $locale->getCode();
                })
                ->toArray()))
            ;
        }

        return $locales;
    }
}
