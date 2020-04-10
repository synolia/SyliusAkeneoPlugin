<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\AttributeOption;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class CreateUpdateDeleteTask implements AkeneoTaskInterface
{
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

    public function __construct(
        EntityManagerInterface $entityManager,
        RepositoryInterface $productAttributeRepository,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->logger = $logger;
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
                $this->processByAttribute($attributeCode, $optionResources['resources'], $optionResources['isMultiple']);
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
        $attribute = $this->productAttributeRepository->findOneBy(['code' => $attributeCode]);

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
                $choices[$option['code']][$locale] = $label;
            }
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
