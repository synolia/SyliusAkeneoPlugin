<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

final class SyliusAkeneoLocaleCodeProvider
{
    /** @var array<string> */
    private array $localesCode;

    public function __construct(
        private AkeneoPimClientInterface $akeneoPimClient,
        private RepositoryInterface $channelRepository,
    ) {
        $this->localesCode = [];
    }

    public function getUsedLocalesOnBothPlatforms(): array
    {
        if ([] !== $this->localesCode) {
            return $this->localesCode;
        }

        foreach ($this->getUsedLocalesOnAkeneo() as $apiLocale) {
            if (false === $apiLocale['enabled'] || !\in_array($apiLocale['code'], $this->getUsedLocalesOnSylius(), true)) {
                continue;
            }
            $this->localesCode[$apiLocale['code']] = $apiLocale['code'];
        }

        return $this->localesCode;
    }

    public function isLocaleDataTranslation(AttributeInterface $attribute, array|string $data, string $locale): bool
    {
        if (\is_array($data)) {
            return $data['locale'] === $locale;
        }

        if (isset($attribute->getConfiguration()['choices'][$data]) && \array_key_exists($locale, $attribute->getConfiguration()['choices'][$data])) {
            return true;
        }

        return false;
    }

    public function isActiveLocale(string $locale): bool
    {
        $locales = $this->getUsedLocalesOnBothPlatforms();

        return \in_array($locale, $locales, true);
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
            $locales = array_unique(array_merge($locales, $channel
                ->getLocales()
                /** @phpstan-ignore-next-line */
                ->map(fn (LocaleInterface $locale): string => (string) $locale->getCode())
                ->toArray()))
            ;
        }

        return $locales;
    }
}
