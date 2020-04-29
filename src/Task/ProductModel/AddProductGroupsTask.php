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
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class AddProductGroupsTask implements AkeneoTaskInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var EntityRepository */
    private $productGroupRepository;

    /** @var LoggerInterface */
    private $logger;

    /** @var int */
    private $groupAlreadyExistCount = 0;

    /** @var int */
    private $groupCreateCount = 0;

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
        $this->type = 'ProductGroups';
        $this->logger->notice(Messages::createOrUpdate($this->type));

        if (!$payload->getResources() instanceof ResourceCursorInterface) {
            throw new NoProductModelResourcesException('No resource found.');
        }

        try {
            foreach ($payload->getResources() as $resource) {
                $this->entityManager->beginTransaction();
                $this->createProductGroups($resource);

                $this->entityManager->flush();
                $this->entityManager->commit();
            }
        } catch (\Throwable $throwable) {
            $this->entityManager->rollback();
            $this->logger->warning($throwable->getMessage());

            throw $throwable;
        }

        $this->logger->notice(Messages::countCreateAndExist('ProductGroup', $this->groupCreateCount, $this->groupAlreadyExistCount));

        return $payload;
    }

    private function createGroupForCode(string $code): void
    {
        if ($this->productGroupRepository->findOneBy(['productParent' => $code])) {
            ++$this->groupAlreadyExistCount;
            $this->logger->info(Messages::hasBeenAlreadyExist('ProductGroup', $code));

            return;
        }

        $productGroup = new ProductGroup();
        $productGroup->setProductParent($code);
        $this->entityManager->persist($productGroup);

        ++$this->groupCreateCount;
        $this->logger->info(Messages::hasBeenCreated('ProductGroup', $code));
    }

    private function createProductGroups(array $resource): void
    {
        if ($resource['parent'] !== null) {
            $this->createGroupForCode($resource['parent']);
        }
        if ($resource['code'] !== null) {
            $this->createGroupForCode($resource['code']);
        }
    }
}
