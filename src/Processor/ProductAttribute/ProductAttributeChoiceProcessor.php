<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Transformer\AttributeOptionValueDataTransformerInterface;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\MultiSelectAttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\SelectAttributeTypeMatcher;

final class ProductAttributeChoiceProcessor implements ProductAttributeChoiceProcessorInterface
{
    public function __construct(
        private ClientFactoryInterface $clientFactory,
        private AttributeTypeMatcher $attributeTypeMatcher,
        private LoggerInterface $akeneoLogger,
        private SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        private AttributeOptionValueDataTransformerInterface $attributeOptionValueDataTransformer,
        private EntityManagerInterface $entityManager,
        private ApiConnectionProviderInterface $apiConnectionProvider,
    ) {
    }

    public function process(
        AttributeInterface $attribute,
        array $resource,
    ): void {
        try {
            $attributeTypeMatcher = $this->attributeTypeMatcher->match($resource['type']);

            if (
                !$attributeTypeMatcher instanceof SelectAttributeTypeMatcher &&
                !$attributeTypeMatcher instanceof MultiSelectAttributeTypeMatcher
            ) {
                return;
            }

            $this->setAttributeChoices(
                $attribute,
                $this->clientFactory->createFromApiCredentials()->getAttributeOptionApi()->all(
                    $resource['code'],
                    $this->apiConnectionProvider->get()->getPaginationSize(),
                ),
                $attributeTypeMatcher instanceof MultiSelectAttributeTypeMatcher,
            );
        } catch (UnsupportedAttributeTypeException $unsupportedAttributeTypeException) {
            $this->akeneoLogger->warning(sprintf(
                '%s: %s',
                $resource['code'],
                $unsupportedAttributeTypeException->getMessage(),
            ));

            return;
        }
    }

    private function setAttributeChoices(
        AttributeInterface $attribute,
        iterable $options,
        bool $isMultiple,
    ): void {
        $choices = [];
        foreach ($options as $option) {
            $transformedCode = $this->attributeOptionValueDataTransformer->transform($option['code']);
            foreach ($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms() as $syliusLocale) {
                $akeneoLocale = $this->syliusAkeneoLocaleCodeProvider->getAkeneoLocale($syliusLocale);

                if (!\array_key_exists($akeneoLocale, $option['labels'])) {
                    $label = \sprintf('[%s]', $transformedCode);
                    $choices[$transformedCode][$syliusLocale] = $label;

                    continue;
                }

                $choices[$transformedCode][$syliusLocale] = $option['labels'][$akeneoLocale];
            }
        }

        if ([] === $choices) {
            $this->entityManager->remove($attribute);

            return;
        }

        $attribute->setConfiguration([
            'choices' => $choices,
            'multiple' => $isMultiple,
            'min' => null,
            'max' => null,
        ]);
    }
}
