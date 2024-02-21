# Use Events

* Before each Task launch in Pipeline an event `BeforeTaskEvent` is dispatched.
* After each Task launch in Pipeline an event `AfterTaskEvent` is dispatched.

These events have two functions :
* `getTask()` : return the Task class name
* `getPayload()` : return the current Payload class name

The Event can modify the Payload which will then be used.

## Before/After processing events

* **Before event** are sent before interracting with the Akeneo resource data.
  This allows you to make changes to the received data.
* **After entity Retrieved event** are sent after creating or finding the entity and before processing it. This is usefull to access the object before .
* **After event** are sent after persisting objects and before flushing them.

### Taxon

* Synolia\SyliusAkeneoPlugin\Event\Category\BeforeProcessingTaxonEvent
* Synolia\SyliusAkeneoPlugin\Event\Category\AfterTaxonRetrievedEvent
* Synolia\SyliusAkeneoPlugin\Event\Category\AfterProcessingTaxonEvent

### Product

* Synolia\SyliusAkeneoPlugin\Event\Product\BeforeProcessingProductEvent
* Synolia\SyliusAkeneoPlugin\Event\Product\AfterProductRetrievedEvent
* Synolia\SyliusAkeneoPlugin\Event\Product\AfterProcessingProductEvent

### Product Variant

* Synolia\SyliusAkeneoPlugin\Event\ProductVariant\BeforeProcessingProductVariantEvent
* Synolia\SyliusAkeneoPlugin\Event\ProductVariant\AfterProductVariantRetrievedEvent
* Synolia\SyliusAkeneoPlugin\Event\ProductVariant\AfterProcessingProductVariantEvent
