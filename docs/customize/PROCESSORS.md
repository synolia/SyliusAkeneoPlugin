# Everything you need to know about using processors to customize your import

## Processing Akeneo attribute values

By default, we use :
* `ProductAttributeAkeneoAttributeProcessor` to insert attribute value to the `ProductAttributeValue` entity.
* `JsonReferenceEntityAttributeValueProcessor` to insert Reference Attribute values as JSON value for the `ProductAttributeValue` entity.

We also provide other processors that could match your need. You can enable them by registering processors as service:

- `Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute\ProductModelAkeneoAttributeProcessor`:
> This service allows you to insert product property by simply matching the attribute name to the model setter
> So you can add new property with the right type to your model without the need to implement his own import logic.

- `Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute\ProductTranslationModelAkeneoAttributeProcessor`:
> This service allows you to insert product translated property by simply matching the attribute name to the model setter
> So you can add new property with the right type to your model without the need to implement his own import logic.
> i.e: description akeneo attribute will be set if the model has setDescription() method.

- `Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute\ProductVariantModelAkeneoAttributeProcessor`:
> This service allows you to insert product property by simply matching the attribute name to the model setter
> So you can add new property with the right type to your model without the need to implement his own import logic.
> i.e.: weight akeneo attribute will be set if the model has setWeight() method.

- `Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute\ProductVariantTranslationModelAkeneoAttributeProcessor`: 
> This service allows you to insert product variant translated property by simply matching the attribute name to the model setter
> So you can add new property with the right type to your model without the need to implement his own import logic.

###  Writing your own logic

#### You can create your own logic by implementing the interface `Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute\AkeneoAttributeProcessorInterface`.

The `$context` parameter contains these values:
```php
[
    'calledBy' => $this, // The instance class who called the processor
    'model' => $model, // This is an instance of ProductInterface or ProductVariantInterface
    'scope' => 'scope', // The current Akeneo scope
    'data' => $data, // The data passed to the processor (akeneo attribute values)
]
```

*i.e.:*
You have added a custom attribute named `externalId` on your ProductVariant entity to be able to map it to an external service.

You can create a class `ProductVariantExternalIdAkeneoAttributeProcessor` that will take care of setting the proper value to your new property.

```php
<?php

namespace App\Processor\ProductAttribute;

use Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute\AkeneoAttributeProcessorInterface;

class ProductVariantExternalIdAkeneoAttributeProcessor implements AkeneoAttributeProcessorInterface
{
    public static function getDefaultPriority(): int
    {
        return 0; // TODO: adjust this value to pass before or after another processor
    }

    public function support(string $attributeCode,array $context = []) : bool
    {
        if(!$context['model'] instanceof \Sylius\Component\Core\Model\ProductVariantInterface) {
            return false;
        }
        
        // i.e: if externalId property is not present, return false otherwise return true 
        
        return true;
    }
    
    public function process(string $attributeCode, array $context = []) : void
    {
        // i.e: here, you have to define your own logic based on the value you received and the attribute properties, 
        // you can take a look at the Synolia\SyliusAkeneoPlugin\Builder\Attribute\ProductAttributeValueValueBuilder
        // which is able to handle the data transformation based on the locale, scope and another attribute's properties.
        $context['model']->setExternalId($context['data']);
    }
}
```
*Your service will be automatically registered because of implementation of `AkeneoAttributeProcessorInterface`,
which is already an [autoconfigured tag](https://symfony.com/doc/current/service_container/tags.html#autoconfiguring-tags).*