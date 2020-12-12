<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\AttributeOption;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;

abstract class AbstractAttributeOptionTask
{
    public const AKENEO_PREFIX = 'akeneo-';

    /** @var int */
    protected $updateCount = 0;

    /** @var int */
    protected $createCount = 0;

    /** @var string */
    protected $type;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var LoggerInterface */
    protected $logger;

    /** @var SyliusAkeneoLocaleCodeProvider */
    protected $syliusAkeneoLocaleCodeProvider;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $akeneoLogger,
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $akeneoLogger;
        $this->syliusAkeneoLocaleCodeProvider = $syliusAkeneoLocaleCodeProvider;
    }

    protected function getUnusedLocale(array $labels): array
    {
        $localeDiff = array_diff($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms(), array_keys($labels));
        if ($localeDiff === []) {
            return [];
        }

        foreach ($localeDiff as $locale) {
            $localeUnused[$locale] = ' ';
        }

        return $localeUnused;
    }

    protected function setAttributeChoices(
        AttributeInterface $attribute,
        iterable $options,
        bool $isMultiple
    ): void {
        $choices = [];
        foreach ($options as $option) {
            foreach ($option['labels'] as $locale => $label) {
                if (!in_array($locale, $this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms(), true)) {
                    continue;
                }
                if (!isset($choices[self::AKENEO_PREFIX . $option['code']]) && $this->getUnusedLocale($option['labels']) !== []) {
                    $choices[self::AKENEO_PREFIX . $option['code']] = $this->getUnusedLocale($option['labels']);
                }
                $choices[self::AKENEO_PREFIX . $option['code']][$locale] = $label;
            }
        }

        if ($choices === []) {
            $this->entityManager->remove($attribute);

            return;
        }

        if (isset($attribute->getConfiguration()['choices'])) {
            ++$this->updateCount;
            $this->logger->info(Messages::hasBeenUpdated($this->type, (string) $attribute->getCode()));
        } else {
            ++$this->createCount;
            $this->logger->info(Messages::hasBeenCreated($this->type, (string) $attribute->getCode()));
        }

        $attribute->setConfiguration([
            'choices' => $choices,
            'multiple' => $isMultiple,
            'min' => null,
            'max' => null,
        ]);
    }
}
