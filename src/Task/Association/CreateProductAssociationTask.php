<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Association;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Sylius\Component\Product\Model\ProductAssociationInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Product\Repository\ProductAssociationTypeRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;
use Synolia\SyliusAkeneoPlugin\Repository\ChannelRepository;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Task\Product\AbstractCreateProductEntities;

final class CreateProductAssociationTask extends AbstractCreateProductEntities implements AkeneoTaskInterface
{
    /** @var FactoryInterface */
    private $productAssociationFactory;

    /** @var EntityRepository */
    private $productAssociationRepository;

    /** @var ProductAssociationTypeRepositoryInterface */
    private $productAssociationTypeRepository;

    public function __construct(
        FactoryInterface $productAssociationFactory,
        EntityRepository $productAssociationRepository,
        ProductAssociationTypeRepositoryInterface $productAssociationTypeRepository,
        EntityManagerInterface $entityManager,
        RepositoryInterface $productVariantRepository,
        RepositoryInterface $productRepository,
        ChannelRepository $channelRepository,
        RepositoryInterface $channelPricingRepository,
        RepositoryInterface $localeRepository,
        RepositoryInterface $productConfigurationRepository,
        ProductVariantFactoryInterface $productVariantFactory,
        FactoryInterface $channelPricingFactory,
        LoggerInterface $akeneoLogger
    ) {
        parent::__construct(
            $entityManager,
            $productVariantRepository,
            $productRepository,
            $channelRepository,
            $channelPricingRepository,
            $localeRepository,
            $productConfigurationRepository,
            $productVariantFactory,
            $channelPricingFactory,
            $akeneoLogger
        );

        $this->productAssociationFactory = $productAssociationFactory;
        $this->productAssociationRepository = $productAssociationRepository;
        $this->productAssociationTypeRepository = $productAssociationTypeRepository;
    }

    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload instanceof ProductPayload) {
            return $payload;
        }

        $query = $this->prepareSelectQuery(true);
        $query->execute();

        while ($results = $query->fetchAllNumeric()) {
            foreach ($results as $result) {
                $resource = \json_decode($result[0], true);

                $this->createAssociationForEachAssociations($resource);
            }
        }

        return $payload;
    }

    private function createAssociationForEachAssociations(array $resource): void
    {
        $product = $this->productRepository->findOneBy(['code' => $resource['identifier']]);
        if (!$product instanceof ProductInterface) {
            $this->logger->critical(sprintf('Product %s not found in database, association skip.', $resource['identifier']));

            return;
        }

        foreach ($resource['associations'] as $associationTypeCode => $associations) {
            $this->associateProducts($product, $associationTypeCode, $associations);
        }

        $this->entityManager->flush();
    }

    private function associateProducts(
        ProductInterface $product,
        string $associationTypeCode,
        array $associations
    ): void {
        $productAssociationType = $this->productAssociationTypeRepository->findOneBy(['code' => $associationTypeCode]);
        if (!$productAssociationType instanceof ProductAssociationTypeInterface) {
            $this->logger->warning(sprintf('Product association type %s not found.', $associationTypeCode));

            return;
        }

        $productAssociation = $this->productAssociationRepository->findOneBy(
            [
                'owner' => $product,
                'type' => $productAssociationType
            ]
        );

        if (!$productAssociation instanceof ProductAssociationInterface) {
            $productAssociation = $this->productAssociationFactory->createNew();
            if (!$productAssociation instanceof ProductAssociationInterface) {
                throw new \LogicException('Not an instance of productAssociation.');
            }
        }

        $productAssociation->setOwner($product);
        $productAssociation->setType($productAssociationType);

        foreach ($associations['products'] as $association) {
            $this->associateProduct($productAssociation, $association);
        }

        $this->entityManager->persist($productAssociation);
    }

    private function associateProduct(ProductAssociationInterface $productAssociation, string $associatedProduct): void
    {
        $product = $this->productRepository->findOneBy(['code' => $associatedProduct]);
        if (!$product instanceof ProductInterface) {
            $this->logger->warning(sprintf('Product %s not and could not be associated', $associatedProduct));

            return;
        }

        $productAssociation->addAssociatedProduct($product);
    }
}
