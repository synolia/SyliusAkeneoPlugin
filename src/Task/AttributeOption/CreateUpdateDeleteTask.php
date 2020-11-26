<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\AttributeOption;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Transformer\AkeneoAttributeToSyliusAttributeTransformer;

final class CreateUpdateDeleteTask implements AkeneoTaskInterface
{
    public const AKENEO_PREFIX = 'akeneo-';

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

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

    /** @var SyliusAkeneoLocaleCodeProvider */
    private $syliusAkeneoLocaleCodeProvider;

    public function __construct(
        EntityManagerInterface $entityManager,
        RepositoryInterface $productAttributeRepository,
        AkeneoAttributeToSyliusAttributeTransformer $akeneoAttributeToSyliusAttributeTransformer,
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        LoggerInterface $akeneoLogger
    ) {
        $this->entityManager = $entityManager;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->logger = $akeneoLogger;
        $this->akeneoAttributeToSyliusAttributeTransformer = $akeneoAttributeToSyliusAttributeTransformer;
        $this->syliusAkeneoLocaleCodeProvider = $syliusAkeneoLocaleCodeProvider;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Option\OptionsPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);
        $this->type = 'Attribute Option';
        $this->logger->notice(Messages::createOrUpdate($this->type));

        try {
            $this->entityManager->beginTransaction();

            foreach ($payload->getResources() as $attributeCode => $optionResources) {
                $this->processByAttribute((string) $attributeCode, $optionResources['resources'], $optionResources['isMultiple']);
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

    private function processByAttribute(
        string $attributeCode,
        ResourceCursorInterface $options,
        bool $isMultiple
    ): void {
        $code = $this->akeneoAttributeToSyliusAttributeTransformer->transform($attributeCode);
        $attribute = $this->productAttributeRepository->findOneBy(['code' => $code]);

        if (!$attribute instanceof AttributeInterface) {
            return;
        }

        $this->setAttributeChoices($attribute, $options, $isMultiple);
    }

    private function setAttributeChoices(
        AttributeInterface $attribute,
        ResourceCursorInterface $options,
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

    private function getUnusedLocale(array $labels): array
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
}
