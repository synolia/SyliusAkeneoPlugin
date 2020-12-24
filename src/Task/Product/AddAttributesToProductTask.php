<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\Processor\MissingAkeneoAttributeProcessorException;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductResourcePayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeProcessorProviderInterface;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class AddAttributesToProductTask implements AkeneoTaskInterface
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeProcessorProviderInterface */
    private $akeneoAttributeProcessorProvider;

    /** @var \Psr\Log\LoggerInterface */
    private $akeneoLogger;

    public function __construct(
        AkeneoAttributeProcessorProviderInterface $akeneoAttributeProcessorProvider,
        LoggerInterface $akeneoLogger
    ) {
        $this->akeneoAttributeProcessorProvider = $akeneoAttributeProcessorProvider;
        $this->akeneoLogger = $akeneoLogger;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Product\ProductResourcePayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload instanceof ProductResourcePayload || !$payload->getProduct() instanceof ProductInterface) {
            return $payload;
        }

        foreach ($payload->getResource()['values'] as $attributeCode => $translations) {
            if ($payload->getProductNameAttribute() === $attributeCode) {
                continue;
            }

            try {
                $processor = $this->akeneoAttributeProcessorProvider->getProcessor($attributeCode, [
                    'calledBy' => $this,
                    'model' => $payload->getProduct(),
                    'scope' => $payload->getScope(),
                    'data' => $translations,
                ]);
                $processor->process($attributeCode, [
                    'calledBy' => $this,
                    'model' => $payload->getProduct(),
                    'scope' => $payload->getScope(),
                    'data' => $translations,
                ]);
            } catch (MissingAkeneoAttributeProcessorException $missingAkeneoAttributeProcessorException) {
                $this->akeneoLogger->debug($missingAkeneoAttributeProcessorException->getMessage());
            }
        }

        return $payload;
    }
}
