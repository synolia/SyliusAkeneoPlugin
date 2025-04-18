<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Asset;

use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\Payload\Asset\AssetPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Processor\Asset\Attribute\AkeneoAssetAttributeProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Task\AbstractBatchTask;

final class BatchAssetTask extends AbstractBatchTask
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        private LoggerInterface $akeneoLogger,
        private AkeneoAssetAttributeProcessorInterface $akeneoAssetAttributeProcessor,
    ) {
        parent::__construct($entityManager);
    }

    /**
     * @param AssetPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->akeneoLogger->debug(self::class);

        $query = $this->getSelectStatement($payload);
        /** @var Result $queryResult */
        $queryResult = $query->executeQuery();

        while ($results = $queryResult->fetchAllAssociative()) {
            foreach ($results as $result) {
                try {
                    $resource = \json_decode((string) $result['values'], true, 512, \JSON_THROW_ON_ERROR);

                    $this->retrieveAssets($payload, $resource);
                    $this->removeEntry($payload, (int) $result['id']);
                } catch (\Throwable $throwable) {
                    $this->akeneoLogger->error($throwable->getMessage());
                    $this->removeEntry($payload, (int) $result['id']);
                }
            }
        }

        $this->entityManager->flush();

        return $payload;
    }

    /**
     * @param AssetPayload $payload
     */
    private function retrieveAssets(PipelinePayloadInterface $payload, array $assetFamilyResource): void
    {
        foreach ($payload->getAkeneoPimClient()->getAssetManagerApi()->all($assetFamilyResource['code']) as $assetResource) {
            $this->handleAssetByFamilyResource($assetFamilyResource['code'], $assetResource);
        }
    }

    private function handleAssetByFamilyResource(string $assetFamilyCode, array $assetResource): void
    {
        foreach ($assetResource['values'] as $attributeCode => $assetAttributeResource) {
            try {
                $this->akeneoAssetAttributeProcessor->process(
                    $assetFamilyCode,
                    $assetResource['code'],
                    $attributeCode,
                    $assetAttributeResource,
                );
            } catch (UnsupportedAttributeTypeException $attributeTypeException) {
                $this->akeneoLogger->warning('Unsupported attribute type', ['ex' => $attributeTypeException]);
            }
            $this->entityManager->flush();
        }
    }
}
