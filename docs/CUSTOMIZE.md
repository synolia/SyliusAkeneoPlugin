# Developers - Customize imports

## Use Events

* Before each Task launch in Pipeline an event `BeforeTaskEvent` is dispatched.
* After each Task launch in Pipeline an event `AfterTaskEvent` is dispatched.

These events have two functions :
* `getTask()` : return the Task class name
* `getPayload()` : return the current Payload class name

The Event can modify the Payload which will then be used.

## Processing Akeneo attribute values

By default, we only use `ProductAttributeAkeneoAttributeProcessor` to insert attribute value to the `ProductAttributeValue` entity, but we provide other processors that you can enable by registering them:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: false

    Synolia\SyliusAkeneoPlugin\Processor\ProductModelAkeneoAttributeProcessor:
        arguments:
            $camelCaseToSnakeCaseNameConverter: '@serializer.name_converter.camel_case_to_snake_case'
            $model: '%sylius.model.product.class%'
        tags:
            - { name: !php/const Synolia\SyliusAkeneoPlugin\Processor\AkeneoAttributeProcessorInterface::TAG_ID }
    
    Synolia\SyliusAkeneoPlugin\Processor\ProductTranslationModelAkeneoAttributeProcessor:
        arguments:
            $camelCaseToSnakeCaseNameConverter: '@serializer.name_converter.camel_case_to_snake_case'
            $model: '%sylius.model.product_translation.class%'
        tags:
            - { name: !php/const Synolia\SyliusAkeneoPlugin\Processor\AkeneoAttributeProcessorInterface::TAG_ID }
    
    Synolia\SyliusAkeneoPlugin\Processor\ProductVariantModelAkeneoAttributeProcessor:
        arguments:
            $camelCaseToSnakeCaseNameConverter: '@serializer.name_converter.camel_case_to_snake_case'
            $model: '%sylius.model.product_variant.class%'
        tags:
            - { name: !php/const Synolia\SyliusAkeneoPlugin\Processor\AkeneoAttributeProcessorInterface::TAG_ID }
    
    Synolia\SyliusAkeneoPlugin\Processor\ProductVariantTranslationModelAkeneoAttributeProcessor:
        arguments:
            $camelCaseToSnakeCaseNameConverter: '@serializer.name_converter.camel_case_to_snake_case'
            $model: '%sylius.model.product_variant_translation.class%'
        tags:
            - { name: !php/const Synolia\SyliusAkeneoPlugin\Processor\AkeneoAttributeProcessorInterface::TAG_ID }
```


###  Writing your own logic

#### You can create your own logic by implementing the interface `Synolia\SyliusAkeneoPlugin\Processor\AkeneoAttributeProcessorInterface` and registering your service.

The `$context` parameter contains these values:
```php
[
    'calledBy' => $this, // The instance class who called the processor
    'model' => $model, // This is an instance of ProductInterface or ProductVariantInterface
    'scope' => 'scope', // The current Akeneo scope
    'data' => $data, // The data passed to the processor (akeneo attribute values)
]
```

*i.e:* 
You have added a custom attribute named `externalId` on your ProductVariant entity to be able to map it to an external service.

You can create a class `ProductVariantExternalIdAkeneoAttributeProcessor` that will take care of setting the proper value to your new property.

```php
<?php

use Synolia\SyliusAkeneoPlugin\Processor\AkeneoAttributeProcessorInterface;

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
        
        // i.e: if externalId property is not present, return false...
        
        return true;
    }
    
    public function process(string $attributeCode,array $context = []) : void
    {
        // i.e: here, you have to define your own logic based on the value you received and the attribute properties
        // you can take a look at the \Synolia\SyliusAkeneoPlugin\Builder\Attribute\ProductAttributeValueValueBuilder
        // which is able to handle the data transformation based on the locale, scope and other properties of the attibute.
        $context['model']->setExternalId($context['data']);
    }
}
```

And then register your service:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: false

    App\Processor\ProductVariantExternalIdAkeneoAttributeProcessor:
        tags:
            - { name: !php/const Synolia\SyliusAkeneoPlugin\Processor\AkeneoAttributeProcessorInterface::TAG_ID }
```


## Override

You can also overload Class with all the facilities of Symfony.

---

Previous step: [Advanced configuration](CONFIGURE_DETAIL.md)

Next step: [Launch import](LAUNCH.md)
