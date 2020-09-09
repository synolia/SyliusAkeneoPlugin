<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ReferenceEntity;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\ExcludedAttributesProvider;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Transformer\AkeneoAttributeToSyliusAttributeTransformer;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;

final class CreateUpdateEntityOptionsTask implements AkeneoTaskInterface
{
    public const AKENEO_PREFIX = 'akeneo-';

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productAttributeFactory;

    /** @var \Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher */
    private $attributeTypeMatcher;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productAttributeRepository;

    /** @var LoggerInterface */
    private $logger;

    /** @var int */
    private $updateCount = 0;

    /** @var int */
    private $createCount = 0;

    /** @var string */
    private $type;

    /** @var AkeneoAttributeToSyliusAttributeTransformer */
    private $akeneoAttributeToSyliusAttributeTransformer;

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\ExcludedAttributesProvider */
    private $excludedAttributesProvider;

    /** @var SyliusAkeneoLocaleCodeProvider */
    private $syliusAkeneoLocaleCodeProvider;

    public function __construct(
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        EntityManagerInterface $entityManager,
        RepositoryInterface $productAttributeRepository,
        AkeneoAttributeToSyliusAttributeTransformer $akeneoAttributeToSyliusAttributeTransformer,
        FactoryInterface $productAttributeFactory,
        AttributeTypeMatcher $attributeTypeMatcher,
        LoggerInterface $akeneoLogger,
        ExcludedAttributesProvider $excludedAttributesProvider
    ) {
        $this->entityManager = $entityManager;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->productAttributeFactory = $productAttributeFactory;
        $this->attributeTypeMatcher = $attributeTypeMatcher;
        $this->logger = $akeneoLogger;
        $this->excludedAttributesProvider = $excludedAttributesProvider;
        $this->akeneoAttributeToSyliusAttributeTransformer = $akeneoAttributeToSyliusAttributeTransformer;
        $this->syliusAkeneoLocaleCodeProvider = $syliusAkeneoLocaleCodeProvider;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\ReferenceEntity\ReferenceEntityOptionsPayload $payload
     *
     * @throws \Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException
     * @throws \Throwable
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);
        $this->type = $payload->getType();
        $this->logger->notice(Messages::createOrUpdate($this->type));

        if (null === $payload->getResources() || 0 === \count($payload->getResources())) {
            throw new NoAttributeResourcesException('No resource found.');
        }

        try {
            $this->entityManager->beginTransaction();

            foreach ($payload->getResources() as $attributeCode => $optionResources) {
                $this->setAttributeChoices(
                    $optionResources['attribute'],
                    $optionResources['resources'],
                    $optionResources['isMultiple']
                );
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $throwable) {
            $this->entityManager->rollback();
            $this->logger->warning($throwable->getMessage());

            throw $throwable;
        }

        $this->logger->notice(Messages::countCreateAndUpdate($this->type, $this->createCount, $this->updateCount));

        return $payload;
    }

    private function setAttributeChoices(
        AttributeInterface $attribute,
        ResourceCursorInterface $options,
        bool $isMultiple
    ): void {
        $choices = [];
        foreach ($options as $option) {
            if (!isset($option['values']['label'])) {
                foreach ($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms() as $locale) {
                    $option['values']['label'][] = [
                        'locale' => $locale,
                        'data' => $attribute->getCode(),
                    ];
                }
            }

            foreach ($option['values']['label'] as $localeArray) {
                if (!in_array($localeArray['locale'], $this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms(), true)) {
                    continue;
                }
                if (!isset($choices[self::AKENEO_PREFIX . $option['code']]) && $this->getUnusedLocale($option['values']['label']) !== []) {
                    $choices[self::AKENEO_PREFIX . $option['code']] = $this->getUnusedLocale($option['values']['label']);
                }
                $choices[self::AKENEO_PREFIX . $option['code']][$localeArray['locale']] = $localeArray['data'];
            }
        }

        if (0 === \count($choices)) {
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

    private function getUnusedLocale(array $labels): array
    {
        $availableAttributeLocales = array_map(function ($locale) {
            return $locale['locale'];
        }, $labels);

        $localeDiff = array_diff($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms(), $availableAttributeLocales);
        if ($localeDiff === []) {
            return [];
        }

        foreach ($localeDiff as $locale) {
            $localeUnused[$locale] = ' ';
        }

        return $localeUnused;
    }
}
