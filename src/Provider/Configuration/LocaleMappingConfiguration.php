<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Configuration;

use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

class LocaleMappingConfiguration implements LocaleMappingConfigurationInterface
{
    private array $syliusLocales = [];

    public function __construct(
        private array $localeMapping,
        private RepositoryInterface $channelRepository,
    ) {
    }

    public function get(): array
    {
        foreach ($this->localeMapping as $akeneoLocale => $syliusLocales) {
            $this->localeMapping[$akeneoLocale] = array_intersect($syliusLocales, $this->getUsedLocalesOnSylius());
        }

        return $this->localeMapping;
    }

    private function getUsedLocalesOnSylius(): array
    {
        if ([] !== $this->syliusLocales) {
            return $this->syliusLocales;
        }

        /** @var \Sylius\Component\Core\Model\ChannelInterface $channel */
        foreach ($this->channelRepository->findAll() as $channel) {
            $this->syliusLocales = array_unique(array_merge($this->syliusLocales, $channel
                ->getLocales()
                /** @phpstan-ignore-next-line */
                ->map(fn (LocaleInterface $locale): string => (string) $locale->getCode())
                ->toArray()));
        }

        return $this->syliusLocales;
    }
}
