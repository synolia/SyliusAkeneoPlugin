<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Service;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

final class SyliusAkeneoLocaleCodeProvider
{
    private RepositoryInterface $channelRepository;

    private AkeneoPimEnterpriseClientInterface $akeneoPimClient;

    /** @var array<string> */
    private array $localesCode = [];

    public function __construct(AkeneoPimEnterpriseClientInterface $akeneoPimClient, RepositoryInterface $channelRepository)
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
        if (is_array($data)) {
            return $data['locale'] === $locale;
        }

        return isset($attribute->getConfiguration()['choices'][$data]) && array_key_exists($locale, $attribute->getConfiguration()['choices'][$data]);
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
        /** @var ChannelInterface $channel */
        foreach ($this->channelRepository->findAll() as $channel) {
            $locales = \array_unique(\array_merge($locales, $channel
                ->getLocales()
                ->map(fn (LocaleInterface $locale) => (string) $locale->getCode())
                ->toArray()))
            ;
        }

        return $locales;
    }
}
