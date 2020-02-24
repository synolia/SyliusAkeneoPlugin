<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Attribute;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Attribute\Factory\AttributeFactory;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcherInterface;

final class CreateUpdateEntityTask implements AkeneoTaskInterface
{
    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productAttributeFactory;

    /** @var \Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher */
    private $attributeTypeMatcher;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productAttributeRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        RepositoryInterface $productAttributeRepository,
        FactoryInterface $productAttributeFactory,
        AttributeTypeMatcher $attributeTypeMatcher
    ) {
        $this->entityManager = $entityManager;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->productAttributeFactory = $productAttributeFactory;
        $this->attributeTypeMatcher = $attributeTypeMatcher;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload $payload
     *
     * @throws \Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException
     * @throws \Throwable
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload->getResources() instanceof ResourceCursorInterface) {
            throw new NoAttributeResourcesException('No resource found.');
        }

        try {
            $this->entityManager->beginTransaction();

            foreach ($payload->getResources() as $resource) {
                try {
                    $attributeType = $this->attributeTypeMatcher->match($resource['type']);

                    /** @var \Sylius\Component\Attribute\Model\AttributeInterface $attribute */
                    $attribute = $this->getOrCreateEntity($resource, $attributeType);

                    foreach ($resource['labels'] as $locale => $label) {
                        $attribute->setCurrentLocale($locale);
                        $attribute->setFallbackLocale($locale);
                        $attribute->setName($label);
                    }
                } catch (UnsupportedAttributeTypeException $unsupportedAttributeTypeException) {
                    continue;
                }
            }

            $this->entityManager->flush();

            $this->entityManager->commit();
        } catch (\Throwable $throwable) {
            $this->entityManager->rollback();

            throw $throwable;
        }

        return $payload;
    }

    private function getOrCreateEntity(array $resource, AttributeTypeMatcherInterface $attributeType): AttributeInterface
    {
        /** @var AttributeInterface $attribute */
        $attribute = $this->productAttributeRepository->findOneBy(['code' => $resource['code']]);

        if (!$attribute instanceof AttributeInterface) {
            if (!$this->productAttributeFactory instanceof AttributeFactory) {
                throw new \LogicException('Wrong Factory');
            }
            /** @var AttributeInterface $attribute */
            $attribute = $this->productAttributeFactory->createTyped($attributeType->getType());
            $attribute->setCode($resource['code']);
            $this->entityManager->persist($attribute);
        }

        return $attribute;
    }
}
