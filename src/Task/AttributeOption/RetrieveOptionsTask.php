<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\AttributeOption;

use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Option\OptionsPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\ConfigurationProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\SelectAttributeTypeMatcher;

final class RetrieveOptionsTask implements AkeneoTaskInterface
{
    /** @var \Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher */
    private $attributeTypeMatcher;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $type;

    /** @var ConfigurationProvider */
    private $configurationProvider;

    public function __construct(
        AttributeTypeMatcher $attributeTypeMatcher,
        LoggerInterface $akeneoLogger,
        ConfigurationProvider $configurationProvider
    ) {
        $this->attributeTypeMatcher = $attributeTypeMatcher;
        $this->logger = $akeneoLogger;
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * @param OptionsPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);
        $this->type = 'Attribute Option';
        $this->logger->notice(Messages::retrieveFromAPI($this->type));

        $compatibleAttributes = [];
        foreach ($payload->getResources() as $resource) {
            try {
                $attributeTypeMatcher = $this->attributeTypeMatcher->match($resource['type']);
                if (!$attributeTypeMatcher instanceof SelectAttributeTypeMatcher) {
                    continue;
                }
                $compatibleAttributes[$resource['code']] = ['isMultiple' => $attributeTypeMatcher->isMultiple($resource['type'])];
            } catch (UnsupportedAttributeTypeException $unsupportedAttributeTypeException) {
                $this->logger->warning(\sprintf(
                    '%s: %s',
                    $resource['code'],
                    $unsupportedAttributeTypeException->getMessage()
                ));

                continue;
            }
        }

        $optionsPayload = $this->process($payload, $compatibleAttributes);
        $this->logger->info(Messages::totalToImport($this->type, count($optionsPayload->getResources())));

        return $optionsPayload;
    }

    /**
     * @param OptionsPayload $payload
     */
    private function process(PipelinePayloadInterface $payload, array $attributeCodes): OptionsPayload
    {
        $optionsPayload = new OptionsPayload($payload->getAkeneoPimClient());
        $resources = [];
        foreach ($attributeCodes as $attributeCode => $values) {
            $resources[$attributeCode] = [
                'isMultiple' => $values['isMultiple'],
                'resources' => $payload->getAkeneoPimClient()->getAttributeOptionApi()->all(
                    $attributeCode,
                    $this->configurationProvider->getConfiguration()->getPaginationSize()
                ),
            ];
        }
        $optionsPayload->setResources($resources);

        return $optionsPayload;
    }
}
