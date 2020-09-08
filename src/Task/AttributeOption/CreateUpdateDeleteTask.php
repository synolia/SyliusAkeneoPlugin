<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\AttributeOption;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Transformer\AkeneoAttributeToSyliusAttributeTransformer;

final class CreateUpdateDeleteTask extends AbstractAttributeOptionTask implements AkeneoTaskInterface
{
    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productAttributeRepository;

    /** @var AkeneoAttributeToSyliusAttributeTransformer */
    private $akeneoAttributeToSyliusAttributeTransformer;

    public function __construct(
        EntityManagerInterface $entityManager,
        RepositoryInterface $productAttributeRepository,
        AkeneoAttributeToSyliusAttributeTransformer $akeneoAttributeToSyliusAttributeTransformer,
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        LoggerInterface $akeneoLogger
    ) {
        parent::__construct($entityManager, $akeneoLogger, $syliusAkeneoLocaleCodeProvider);

        $this->entityManager = $entityManager;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->akeneoAttributeToSyliusAttributeTransformer = $akeneoAttributeToSyliusAttributeTransformer;
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
        $code = $this->akeneoAttributeToSyliusAttributeTransformer->transform($attributeCode);
        $attribute = $this->productAttributeRepository->findOneBy(['code' => $code]);

        if (!$attribute instanceof AttributeInterface) {
            return;
        }

        $this->setAttributeChoices($attribute, $options, $isMultiple);
    }
}
