# Developers - Using fixtures

This plugin provides multiples fixtures to help you start your shop from scratch.

## Category Configurations - `akeneo_category_configuration`

This fixture allow to define the root category you want to import from Akeneo, and also if you want to exclude somes.

```yaml
# config/packages/sylius_fixtures.yaml

sylius_fixtures:
    suites:
        app:
            fixtures:
                akeneo_category_configuration:
                    options: 
                        root_categories_to_import:
                            - 'root_category'
                            - 'another_root_category'
                        categories_to_exclude:
                            - 'excluded_category'
                            - 'another_excluded_category'
```
## Categories - `akeneo_categories`

This fixture allow you to trigger the `akeneo:import:categories` command.

> [!IMPORTANT]
> Remember that the `akeneo_category_configuration` fixture must be configured and loaded before this one.

```yaml
# config/packages/sylius_fixtures.yaml

sylius_fixtures:
    suites:
        app:
            fixtures:
                akeneo_category_configuration: 
                    # ...
                akeneo_categories: ~
```

You can also provide a custom filter to reduce the number of categories you want to import.

> [!IMPORTANT]
> If you want to get only some children categories, make sure that all parents are also included in your search. 

Inside `custom` node option, you can provide any query parameter supported by [Akeneo API](https://api.akeneo.com/documentation/filter.html#filter-categories).

```yaml
# config/packages/sylius_fixtures.yaml

sylius_fixtures:
    suites:
        app:
            fixtures:
                akeneo_category_configuration: 
                    # ...
                akeneo_categories: 
                    options:
                        custom:
                            search:
                                code: 
                                    - 
                                        operator: 'IN'
                                        value: 
                                            - 'root_category'
                                            - 'child_category'
                                            - 'another_child_category'
```

## Attributes - `akeneo_attributes`

This fixture allow you to trigger the `akeneo:import:attributes` command.

You can specify the batch size, if you allow parallel import, and max concurrency. 

```yaml
# config/packages/sylius_fixtures.yaml

sylius_fixtures:
    suites:
        app:
            fixtures:
                akeneo_attributes: 
                    options:
                        batch_size: 100
                        allow_parallel: true
                        max_concurrency: 4
```

If you have too many attributes, you can also provide a custom filter to reduce the number of attributes you want to import.

```yaml
# config/packages/sylius_fixtures.yaml

sylius_fixtures:
    suites:
        app:
            fixtures:
                akeneo_attributes: 
                    options:
                        custom:
                            search:
                                code: 
                                    - 
                                        operator: 'IN'
                                        value: 
                                            - 'attribute_code'
                                            - 'another_attribute_code'
```
Inside `custom` node option, you can provide any query parameter supported by [Akeneo API](https://api.akeneo.com/documentation/filter.html#filter-attributes).

## Association Types - `akeneo_association_types`

This fixture allow you to trigger the `akeneo:import:association-type` command.

You can specify the batch size, if you allow parallel import, and max concurrency. 

```yaml
# config/packages/sylius_fixtures.yaml

sylius_fixtures:
    suites:
        app:
            fixtures:
                akeneo_association_types: 
                    options:
                        batch_size: 100
                        allow_parallel: true
                        max_concurrency: 4
```

> [!NOTE]
> Given [Akeneo API](https://api.akeneo.com/api-reference.html#Associationtype) don't provide any search filter for association types, 
> it's not currently possible de reduce the number of association types to import.

## Families - `akeneo_families`

This fixture allow you to trigger the `akeneo:import:families` command.

You can specify the batch size, if you allow parallel import, and max concurrency.

```yaml
# config/packages/sylius_fixtures.yaml

sylius_fixtures:
    suites:
        app:
            fixtures:
                akeneo_families: 
                    options:
                        batch_size: 100
                        allow_parallel: true
                        max_concurrency: 4
```

You can also provide a custom filter to reduce the number of families you want to import.

Inside `custom` node option, you can provide any query parameter supported by [Akeneo API](https://api.akeneo.com/documentation/filter.html#filter-on-product-model-properties).

```yaml
# config/packages/sylius_fixtures.yaml

sylius_fixtures:
    suites:
        app:
            fixtures:
                akeneo_families: 
                    options:
                        custom:
                            search:
                                categories:
                                    -
                                        operator: 'IN'
                                        value: 
                                            - 'root_category'
```

> [!NOTE]
> The API call made is on ProductModel endpoint, so be careful to only use filters available for this endpoint.

## Product Models - `akeneo_product_models`

This fixture allow you to trigger the `akeneo:import:product-models` command.

You can specify the batch size, if you allow parallel import, and max concurrency.

```yaml
# config/packages/sylius_fixtures.yaml

sylius_fixtures:
    suites:
        app:
            fixtures:
                akeneo_product_models: 
                    options:
                        batch_size: 100
                        allow_parallel: true
                        max_concurrency: 4
```

> [!IMPORTANT]
> Before this fixture, you need to create a [Product Filter rule](https://github.com/synolia/SyliusAkeneoPlugin/blob/master/docs/CONFIGURE_DETAIL.md#product-filter-rules).

You can also provide a custom filter to reduce the number of product-models you want to import.

Inside `custom` node option, you can provide any query parameter supported by [Akeneo API](https://api.akeneo.com/documentation/filter.html#filter-on-product-model-properties).

```yaml
sylius_fixtures:
    suites:
        app:
            fixtures:
                akeneo_product_models:
                    options:
                        custom:
                            search:
                                categories:
                                    -
                                        operator: 'IN'
                                        value:
                                            - 'root_category'
                                            - 'another_category'
                                parent:
                                    - 
                                        operator: 'NOT EMPTY'
```

> [!NOTE]
> Adding NOT EMPTY filter for parent will avoid warning while importing product models. 
> We actually don't import productModel without parents.


## Products - `akeneo_products`

This fixture allow you to trigger the `akeneo:import:products` command.

You can specify the batch size, if you allow parallel import, and max concurrency.

```yaml
# config/packages/sylius_fixtures.yaml

sylius_fixtures:
    suites:
        app:
            fixtures:
                akeneo_products: 
                    options:
                        batch_size: 100
                        allow_parallel: true
                        max_concurrency: 4
```

Like others fixtures, you can reduce amount of product to import by adding filters.
Inside `custom` node option, you can provide any query parameter supported by [Akeneo API](https://api.akeneo.com/documentation/filter.html#filter-on-product-properties).


```yaml
# config/packages/sylius_fixtures.yaml

sylius_fixtures:
    suites:
        app:
            fixtures:
                akeneo_products: 
                    options:
                        custom:
                            search:
                                code:
                                    -
                                        operator: 'IN'
                                        value:
                                            - 'awesome_product'
```

> [!NOTE]
> While launching this fixture, the [Product Filter rule](https://github.com/synolia/SyliusAkeneoPlugin/blob/master/docs/CONFIGURE_DETAIL.md#product-filter-rules) will be applied too.
> Custom search can override those values.




