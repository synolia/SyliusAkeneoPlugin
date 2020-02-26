<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Option;

use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Option\OptionsPayload;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\SelectAttributeTypeMatcher;

final class RetrieveOptionsTask implements AkeneoTaskInterface
{
    /** @var \Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher */
    private $attributeTypeMatcher;

    public function __construct(AttributeTypeMatcher $attributeTypeMatcher)
    {
        $this->attributeTypeMatcher = $attributeTypeMatcher;
    }

    /**
     * @param OptionsPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $compatibleAttributes = [];
        foreach ($payload->getResources() as $resource) {
            try {
                $attributeTypeMatcher = $this->attributeTypeMatcher->match($resource['type']);
                if (!$attributeTypeMatcher instanceof SelectAttributeTypeMatcher) {
                    continue;
                }
                $compatibleAttributes[$resource['code']] = ['isMultiple' => $attributeTypeMatcher->isMultiple($resource['type'])];
            } catch (UnsupportedAttributeTypeException $unsuportedAttributeTypeException) {
                continue;
            }
        }

        return $this->process($payload, $compatibleAttributes);
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
                'resources' => $payload->getAkeneoPimClient()->getAttributeOptionApi()->all($attributeCode),
            ];
        }
        $optionsPayload->setResources($resources);

        return $optionsPayload;
    }
}
