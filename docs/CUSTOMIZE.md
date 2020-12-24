# Developers - Customize imports

## Use Events

* Before each Task launch in Pipeline an event `BeforeTaskEvent` is dispatched.
* After each Task launch in Pipeline an event `AfterTaskEvent` is dispatched.

These events have two functions :
* `getTask()` : return the Task class name
* `getPayload()` : return the current Payload class name

The Event can modify the Payload which will then be used.

## Writing your own logic
### ProductAttribute 
By default, we only use ProductAttributeAkeneoAttributeProcessor to insert attribute data to ProductAttributeValue entity but we provide other processors that you can enable by registering them:

```yaml
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

You can create your own logic by implementing the interface `Synolia\SyliusAkeneoPlugin\Processor\AkeneoAttributeProcessorInterface` and registering your service.
i.e: 
You have added a custom attribute named `externalId` for your ProductVariant to be able to map it to another service.
You can crate a class `ProductVariantExternalIdAkeneoAttributeProcessor`

The `$context` variable contains these values:
```php
[
    'calledBy' => $this, // The instance class who called the processor
    'model' => $model, // This is an instance of ProductInterface or ProductVariantInterface
    'scope' => 'scope', // The current Akeneo scope
    'data' => $data, // The data passed to the processor (akeneo attribute values)
]
```

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
        
        //i.e: if externalId property is not present, return false...
        
        return true;
    }
    
    public function process(string $attributeCode,array $context = []) : void
    {
        if(!$context['model'] instanceof \Sylius\Component\Core\Model\ProductVariantInterface) {
            return;
        }
    
        $context['model']->setExternalId($context['data']);
    }
}
```

And then register your service:

```yaml
    App\Processor\ProductVariantExternalIdAkeneoAttributeProcessor:
        tags:
            - { name: !php/const Synolia\SyliusAkeneoPlugin\Processor\AkeneoAttributeProcessorInterface::TAG_ID }
```


## Override

You can also overload Class with all the facilities of Symfony.

---

Previous step: [Advanced configuration](CONFIGURE_DETAIL.md)

Next step: [Launch import](LAUNCH.md)
