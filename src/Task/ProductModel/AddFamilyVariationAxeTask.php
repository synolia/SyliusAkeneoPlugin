<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ProductModel;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroup;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoProductModelResourcesException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Processor\ProductGroup\FamilyVariationAxeProcessor;
use Synolia\SyliusAkeneoPlugin\Retriever\FamilyRetriever;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class AddFamilyVariationAxeTask implements AkeneoTaskInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var EntityRepository */
    private $productGroupRepository;

    /** @var FamilyRetriever */
    private $familyRetriever;

    /** @var FamilyVariationAxeProcessor */
    private $familyVariationAxeProcessor;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $type;

    public function __construct(
        EntityManagerInterface $entityManager,
        EntityRepository $productGroupRepository,
        FamilyRetriever $familyRetriever,
        FamilyVariationAxeProcessor $familyVariationAxeProcessor,
        LoggerInterface $akeneoLogger
    ) {
        $this->entityManager = $entityManager;
        $this->productGroupRepository = $productGroupRepository;
        $this->familyRetriever = $familyRetriever;
        $this->logger = $akeneoLogger;
        $this->familyVariationAxeProcessor = $familyVariationAxeProcessor;
    }

    /**
     * @param ProductModelPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);
        $this->type = 'FamilyVariationAxe';
        $this->logger->notice(Messages::createOrUpdate($this->type));

        $this->addFamilyVariationAxe($payload);

        $this->logger->notice(Messages::countItems($this->type, $this->familyVariationAxeProcessor->itemCount));

        return $payload;
    }

    private function addFamilyVariationAxe(ProductModelPayload $payload): void
    {
        if (!$payload->getModelResources() instanceof ResourceCursorInterface) {
            throw new NoProductModelResourcesException('No resource found.');
        }

        try {
            $familiesVariantPayloads = [];
            $this->entityManager->beginTransaction();
            foreach ($payload->getModelResources() as $resource) {
                $familiesVariantPayloads = $this->familyVariationAxeProcessor->process($payload, $resource, $familiesVariantPayloads);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $throwable) {
            $this->entityManager->rollback();
            $this->logger->warning($throwable->getMessage());

            throw $throwable;
        }
    }
}
