<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Option;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class CreateUpdateDeleteTask implements AkeneoTaskInterface
{
    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productAttributeRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        RepositoryInterface $productAttributeRepository
    ) {
        $this->entityManager = $entityManager;
        $this->productAttributeRepository = $productAttributeRepository;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Option\OptionsPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        foreach ($payload->getResources() as $attributeCode => $optionResources) {
            $this->processByAttribute($attributeCode, $optionResources['resources'], $optionResources['isMultiple']);
        }

        $this->entityManager->flush();

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

        $this->getOrCreateOption($attribute, $options, $isMultiple);
    }

    private function getOrCreateOption(
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

        $attribute->setConfiguration([
            'choices' => $choices,
            'multiple' => $isMultiple,
            'min' => null,
            'max' => null,
        ]);
    }
}
