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
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class AddFamilyVariationAxeTask implements AkeneoTaskInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var EntityRepository */
    private $productGroupRepository;

    /** @var LoggerInterface */
    private $logger;

    /** @var int */
    private $itemCount = 0;

    /** @var string */
    private $type;

    public function __construct(
        EntityManagerInterface $entityManager,
        EntityRepository $productGroupRepository,
        LoggerInterface $akeneoLogger
    ) {
        $this->entityManager = $entityManager;
        $this->productGroupRepository = $productGroupRepository;
        $this->logger = $akeneoLogger;
    }

    /**
     * @param ProductModelPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);
        $this->type = 'FamilyVariationAxe';
        $this->logger->notice(Messages::createOrUpdate($this->type));

        if (!$payload->getResources() instanceof ResourceCursorInterface) {
            throw new NoProductModelResourcesException('No resource found.');
        }

        try {
            $this->entityManager->beginTransaction();
            foreach ($payload->getResources() as $resource) {
                if ($resource['parent'] !== null) {
                    continue;
                }

                $productGroup = $this->productGroupRepository->findOneBy(['productParent' => $resource['code']]);
                if (!$productGroup instanceof ProductGroup) {
                    continue;
                }

                $payloadProductGroup = $payload->getAkeneoPimClient()->getFamilyVariantApi()->get($resource['family'], $resource['family_variant']);

                foreach ($payloadProductGroup['variant_attribute_sets'] as $variantAttributeSet) {
                    if (count($payloadProductGroup['variant_attribute_sets']) !== $variantAttributeSet['level']) {
                        continue;
                    }

                    foreach ($variantAttributeSet['axes'] as $axe) {
                        $productGroup->addVariationAxe($axe);
                        ++$this->itemCount;
                        $this->logger->info(Messages::setVariationAxeToFamily($this->type, $resource['family'], $axe));
                    }
                }
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $throwable) {
            $this->entityManager->rollback();
            $this->logger->warning($throwable->getMessage());

            throw $throwable;
        }

        $this->logger->notice(Messages::countItems($this->type, $this->itemCount));

        return $payload;
    }
}
