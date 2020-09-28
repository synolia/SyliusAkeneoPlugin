<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Attribute;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Attribute\Factory\AttributeFactory;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\ReferenceEntityAttribute\ReferenceEntityAttributeTypeMatcherInterface;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\TypeMatcherInterface;

abstract class AbstractAttributeTask
{
    /** @var int */
    protected $updateCount = 0;

    /** @var int */
    protected $createCount = 0;

    /** @var string */
    protected $type;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var RepositoryInterface */
    protected $productAttributeRepository;

    /** @var FactoryInterface */
    protected $productAttributeFactory;

    /** @var LoggerInterface */
    protected $logger;

    /** @var SyliusAkeneoLocaleCodeProvider */
    protected $syliusAkeneoLocaleCodeProvider;

    public function __construct(
        EntityManagerInterface $entityManager,
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        RepositoryInterface $productAttributeRepository,
        FactoryInterface $productAttributeFactory,
        LoggerInterface $akeneoLogger
    ) {
        $this->entityManager = $entityManager;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->productAttributeFactory = $productAttributeFactory;
        $this->logger = $akeneoLogger;
        $this->syliusAkeneoLocaleCodeProvider = $syliusAkeneoLocaleCodeProvider;
    }

    protected function setAttributeTranslations(array $labels, AttributeInterface $attribute): void
    {
        if (empty($labels)) {
            foreach ($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms() as $locale) {
                $attribute->setCurrentLocale($locale);
                $attribute->setFallbackLocale($locale);
                $attribute->setName(\sprintf('[%s]', $attribute->getCode()));
            }

            return;
        }

        foreach ($labels as $locale => $label) {
            if (!in_array($locale, $this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms(), true)) {
                continue;
            }

            if (!is_string($locale)) {
                continue;
            }

            $attribute->setCurrentLocale($locale);
            $attribute->setFallbackLocale($locale);
            $attribute->setName($label);
        }
    }

    protected function getOrCreateEntity(string $attributeCode, TypeMatcherInterface $attributeType): AttributeInterface
    {
        /** @var AttributeInterface $attribute */
        $attribute = $this->productAttributeRepository->findOneBy(['code' => $attributeCode]);

        if (!$attribute instanceof AttributeInterface) {
            if (!$this->productAttributeFactory instanceof AttributeFactory) {
                throw new \LogicException('Wrong Factory');
            }
            /** @var AttributeInterface $attribute */
            $attribute = $this->productAttributeFactory->createTyped($attributeType->getType());

            if ($attributeType instanceof ReferenceEntityAttributeTypeMatcherInterface) {
                $attribute->setStorageType($attributeType->getStorageType());
            }

            $attribute->setCode($attributeCode);
            $this->entityManager->persist($attribute);
            ++$this->createCount;
            $this->logger->info(Messages::hasBeenCreated($this->type, (string) $attribute->getCode()));

            return $attribute;
        }

        ++$this->updateCount;
        $this->logger->info(Messages::hasBeenUpdated($this->type, (string) $attribute->getCode()));

        return $attribute;
    }
}
