<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Association;

use Akeneo\Pim\ApiClient\Pagination\Page;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException;
use Synolia\SyliusAkeneoPlugin\Payload\Association\AssociationTypePayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class AddAssociationTypeTask implements AkeneoTaskInterface
{
    /** @var FactoryInterface */
    private $productAssociationTypeFactory;

    /** @var FactoryInterface */
    private $productAssociationTypeTranslationFactory;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        FactoryInterface $productAssociationTypeFactory,
        FactoryInterface $productAssociationTypeTranslationFactory,
        EntityManagerInterface $entityManager
    ) {
        $this->productAssociationTypeFactory = $productAssociationTypeFactory;
        $this->productAssociationTypeTranslationFactory = $productAssociationTypeTranslationFactory;
        $this->entityManager = $entityManager;
    }

    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload instanceof AssociationTypePayload) {
            return $payload;
        }

        $resources = $payload->getResources();
        if (!$resources instanceof Page) {
            throw new NoAttributeResourcesException('No resource found.');
        }

        foreach ($resources->getItems() as $resource) {
            $productAssociationType = $this->productAssociationTypeFactory->createNew();
            if (!$productAssociationType instanceof ProductAssociationTypeInterface) {
                throw new \LogicException('Unknown error.');
            }

            $productAssociationType->setCode($resource['code']);
            $this->addTranslations($resource, $productAssociationType);

            $this->entityManager->persist($productAssociationType);
        }

        $this->entityManager->flush();

        return $payload;
    }

    private function addTranslations(array $resource, ProductAssociationTypeInterface $productAssociationType): void
    {
        foreach ($resource['labels'] as $localeCode => $label) {
            $productAssociationType->addTranslation($this->createTranslation($localeCode, $label));
        }
    }

    private function createTranslation(string $localeCode, string $label): ProductAssociationTypeTranslationInterface
    {
        $productAssociationTypeTranslation = $this->productAssociationTypeTranslationFactory->createNew();
        if (!$productAssociationTypeTranslation instanceof ProductAssociationTypeTranslationInterface) {
            throw new \LogicException('Unknown error.');
        }

        $productAssociationTypeTranslation->setLocale($localeCode);
        $productAssociationTypeTranslation->setName($label);

        return $productAssociationTypeTranslation;
    }
}
