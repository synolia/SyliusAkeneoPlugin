<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\ReferenceEntityAttribute;

use Synolia\SyliusAkeneoPlugin\Provider\AkeneoReferenceEntityAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Task\AttributeOption\CreateUpdateDeleteTask;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\ReferenceEntityAttribute\ReferenceEntityAttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\ReferenceEntityAttribute\SingleOptionAttributeTypeMatcher;

final class SelectProductReferenceEntityAttributeValueValueBuilder implements ProductReferenceEntityAttributeValueValueBuilderInterface
{
    /** @var AkeneoReferenceEntityAttributePropertiesProvider */
    private $akeneoReferenceEntityAttributePropertiesProvider;

    /** @var ReferenceEntityAttributeTypeMatcher */
    private $referenceEntityAttributeTypeMatcher;

    public function __construct(
        AkeneoReferenceEntityAttributePropertiesProvider $akeneoReferenceEntityAttributePropertiesProvider,
        ReferenceEntityAttributeTypeMatcher $referenceEntityAttributeTypeMatcher
    ) {
        $this->akeneoReferenceEntityAttributePropertiesProvider = $akeneoReferenceEntityAttributePropertiesProvider;
        $this->referenceEntityAttributeTypeMatcher = $referenceEntityAttributeTypeMatcher;
    }

    public function support(string $referenceEntityCode, string $subAttributeCode): bool
    {
        return $this->referenceEntityAttributeTypeMatcher->match(
                $this->akeneoReferenceEntityAttributePropertiesProvider->getType(
                    $referenceEntityCode,
                    $subAttributeCode)
            ) instanceof SingleOptionAttributeTypeMatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function build($value)
    {
        return [CreateUpdateDeleteTask::AKENEO_PREFIX . $value];
    }
}
