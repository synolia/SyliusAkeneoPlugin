<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Component\TaxonAttribute\Configuration;

use Symfony\Component\Form\AbstractType;

final class ImageConfigurationAttributeType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return 'sylius_taxon_attribute_type_configuration_image';
    }
}
