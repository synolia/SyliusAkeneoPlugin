<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Mapper\AkeneoSyliusLocaleMapperInterface;
use Synolia\SyliusAkeneoPlugin\Mapper\LocaleNotFoundException;

final class SyliusAkeneoLocaleCodeProvider
{
    /** @var array<string> */
    private array $localesCode;

    public function __construct(
        private AkeneoPimClientInterface $akeneoPimClient,
        private RepositoryInterface $channelRepository,
        private AkeneoSyliusLocaleMapperInterface $akeneoSyliusLocaleMapper,
        private string $defaultSyliusLocaleCode,
    ) {
        $this->localesCode = [];
    }

    public function getUsedLocalesOnBothPlatforms(): array
    {
        if ([] !== $this->localesCode) {
            return $this->localesCode;
        }

        /**
         * @var array{enabled: bool, code: string} $apiLocale
         */
        foreach ($this->getUsedLocalesOnAkeneo() as $apiLocale) {
            if (false === $apiLocale['enabled']) {
                continue;
            }

            foreach ($this->akeneoSyliusLocaleMapper->map($apiLocale['code']) as $syliusLocaleMapping) {
                if (!in_array($syliusLocaleMapping, $this->getUsedLocalesOnSylius())) {
                    continue;
                }

                $this->localesCode[] = $syliusLocaleMapping;
            }
        }

        if (!array_key_exists($this->defaultSyliusLocaleCode, $this->localesCode)) {
            $this->localesCode[] = $this->defaultSyliusLocaleCode;
        }

        return $this->localesCode = array_unique($this->localesCode);
    }

    public function getAkeneoLocale(string $syliusLocale): string
    {
        return $this->akeneoSyliusLocaleMapper->unmap($syliusLocale);
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
                ->toArray()));
        }

        return $locales;
    }

    public function getUsedAkeneoLocales(): array
    {
        $locales = [];

        foreach ($this->getUsedLocalesOnSylius() as $syliusLocale) {
            try {
                $locales[] = $this->getAkeneoLocale($syliusLocale);
            } catch (LocaleNotFoundException) {
            }
        }

        return $locales;
    }
}
