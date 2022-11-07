# Everything you need to know about Asset in Sylius

## How are they stored?

As storing content of an asset in a Sylius Attribute is difficult, and we don't want to duplicate his data everywhere, a new table stores the content of each asset.

This task is done by executing the import assets command.

### On Asset table

The value is stored as json inside an "url" node in the asset content column.

### On ProductAttribute entity

The attribute configuration is stored as normal attribute. The configuration contains all the available values and their translations.

This task is done while importing product models.

### On ProductAttributeValue entity

Currently, we only support asset attribute of type "media_link".
We only store the asset code inside the value, and we expose the assets node inside the product.
