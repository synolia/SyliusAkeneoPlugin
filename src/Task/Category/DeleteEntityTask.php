<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Category;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\Taxon;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoCategoryResourcesException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Repository\ProductRepository;
use Synolia\SyliusAkeneoPlugin\Repository\TaxonRepository;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class DeleteEntityTask implements AkeneoTaskInterface
{
    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    /** @var \Synolia\SyliusAkeneoPlugin\Repository\TaxonRepository */
    private $taxonRepository;

    /** @var \Synolia\SyliusAkeneoPlugin\Repository\ProductRepository */
    private $productRepository;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $type;

    /** @var int */
    private $deleteCount = 0;

    /** @var \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface */
    private $parameterBag;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProductRepository $productAkeneoRepository,
        TaxonRepository $taxonAkeneoRepository,
        LoggerInterface $akeneoLogger,
        ParameterBagInterface $parameterBag
    ) {
        $this->entityManager = $entityManager;
        $this->productRepository = $productAkeneoRepository;
        $this->taxonRepository = $taxonAkeneoRepository;
        $this->logger = $akeneoLogger;
        $this->parameterBag = $parameterBag;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);
        $this->logger->notice(Messages::removalNoLongerExist($payload->getType()));
        $this->type = $payload->getType();

        if (!\is_array($payload->getResources())) {
            throw new NoCategoryResourcesException('No resource found.');
        }

        /** To be used for categories removal */
        $codes = [];

        try {
            $this->entityManager->beginTransaction();

            foreach ($payload->getResources() as $resource) {
                $codes[] = $resource['code'];
            }

            $this->removeUnusedCategories($codes);
            $this->logger->notice(Messages::countOfDeleted($payload->getType(), $this->deleteCount));

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $throwable) {
            $this->entityManager->rollback();
            $this->logger->warning($throwable->getMessage());

            throw $throwable;
        }

        return $payload;
    }

    private function removeUnusedCategories(array $codes): void
    {
        /** @var array $taxonIdsArray */
        $taxonIdsArray = $this->taxonRepository->getMissingCategoriesIds($codes);

        /** @var array $taxonIds */
        $taxonIds = \array_map(function (array $data) {
            return $data['id'];
        }, $taxonIdsArray);

        //Avoid having same ID multiple times
        $taxonIds = \array_unique($taxonIds);
        //Sort descending order of taxon ID to delete childs first
        \rsort($taxonIds);

        //unset main taxon from products
        $products = $this->productRepository->findProductsUsingCategories($taxonIds);

        /** @var Product $product */
        foreach ($products as $product) {
            $product->setMainTaxon(null);
        }

        foreach ($taxonIds as $taxonId) {
            /** @var TaxonInterface $taxon */
            $taxon = $this->entityManager->getReference($this->parameterBag->get('sylius.model.taxon.class'), $taxonId);
            if (!$taxon instanceof TaxonInterface) {
                continue;
            }

            $this->entityManager->remove($taxon);
            $this->logger->info(Messages::hasBeenDeleted($this->type, (string) $taxon->getCode()));
            ++$this->deleteCount;
        }
    }
}
