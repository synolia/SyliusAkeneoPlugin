# UPGRADE FROM `v4.x` TO `v5.0.0`

* **BC BREAK**: The plugin now requires Sylius 2. Follow the [Sylius 2 upgrade guide](https://github.com/Sylius/Sylius/blob/2.0/UPGRADE-2.0.md) for your project first.
* **BC BREAK**: The plugin follows the Symfony bundle directory structure: `src/Resources/config` moved to `config`,
  `src/Resources/views` to `templates`, `src/Resources/translations` to `translations` and `src/Migrations` to `migrations`.
  * Update the config import in `config/packages/_sylius.yaml`: `@SynoliaSyliusAkeneoPlugin/config/config.yaml`
    instead of `@SynoliaSyliusAkeneoPlugin/Resources/config/config.yaml`.
  * Update the routing import in `config/routes.yaml`: `@SynoliaSyliusAkeneoPlugin/config/routes.yaml`
    instead of `@SynoliaSyliusAkeneoPlugin/Resources/config/routes.yaml`.
* **BC BREAK**: The admin pages are built with Sylius 2 Twig hooks (see `config/twig_hooks.yaml`).
  Templates overridden from `@SynoliaSyliusAkeneoPlugin/Admin/...` are no longer used: move your customizations to Twig hooks
  or override the new templates in `@SynoliaSyliusAkeneoPlugin/admin/...`.
* **BC BREAK**: The Akeneo API configuration stored in database, deprecated in v4, has been removed:
  the admin page `/admin/akeneo/api/configuration`, the `ApiConfiguration` entity, its controller, form, menu entry,
  the `akeneo_api_configuration` fixture and `DatabaseApiConfigurationProvider`.
  * Configure the API with environment variables and the `synolia_sylius_akeneo.api_configuration` configuration, see [docs/CONFIGURE.md](docs/CONFIGURE.md).
  * The `akeneo_api_configuration` table is no longer used: you can drop it with a migration in your project.
* **BC BREAK**: Services are now tagged with the FQCN of their interface instead of a `sylius.akeneo.*` tag, and the `TAG_ID` constants have been removed.
  Services implementing these interfaces are still autoconfigured. If you tag services manually or use `TAG_ID`, use the interface FQCN instead:

  | Removed tag | Interface FQCN to use as tag |
  |---|---|
  | `sylius.akeneo.asset_value_builder` | `Synolia\SyliusAkeneoPlugin\Builder\Asset\Attribute\AssetAttributeValueBuilderInterface` |
  | `sylius.akeneo.attribute_value_value_builder` | `Synolia\SyliusAkeneoPlugin\Builder\Attribute\ProductAttributeValueValueBuilderInterface` |
  | `sylius.akeneo.dynamic_option_value_builder` | `Synolia\SyliusAkeneoPlugin\Builder\ProductOptionValue\DynamicOptionValueBuilderInterface` |
  | `sylius.akeneo.dynamic_option_value_translation_builder` | `Synolia\SyliusAkeneoPlugin\Builder\ProductOptionValueTranslation\ProductOptionValueTranslationBuilderInterface` |
  | `sylius.akeneo.taxon.attribute_value_builder` | `Synolia\SyliusAkeneoPlugin\Builder\TaxonAttribute\TaxonAttributeValueBuilderInterface` |
  | `sylius.akeneo.category_processor` | `Synolia\SyliusAkeneoPlugin\Processor\Category\CategoryProcessorInterface` |
  | `sylius.akeneo.product_processor` | `Synolia\SyliusAkeneoPlugin\Processor\Product\ProductProcessorInterface` |
  | `sylius.akeneo.attribute_processor` | `Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute\AkeneoAttributeProcessorInterface` |
  | `sylius.akeneo.reference_entity_attribute_value_processor` | `Synolia\SyliusAkeneoPlugin\Processor\ProductAttributeValue\ReferenceEntity\ReferenceEntityAttributeValueProcessorInterface` |
  | `sylius.akeneo.table_product_attribute_value_processor` | `Synolia\SyliusAkeneoPlugin\Processor\ProductAttributeValue\Table\TableProductAttributeValueProcessorInterface` |
  | `sylius.akeneo.option_values_processor` | `Synolia\SyliusAkeneoPlugin\Processor\ProductOptionValue\OptionValuesProcessorInterface` |
  | `sylius.akeneo.product_variant_processor` | `Synolia\SyliusAkeneoPlugin\Processor\ProductVariant\ProductVariantProcessorInterface` |
  | `sylius.akeneo.data_migration_transformer` | `Synolia\SyliusAkeneoPlugin\Transformer\DataMigration\DataMigrationTransformerInterface` |
  | `sylius.akeneo.asset_attribute_type_matcher` | `Synolia\SyliusAkeneoPlugin\TypeMatcher\Asset\Attribute\AssetAttributeTypeMatcherInterface` |
  | `sylius.akeneo.type_matcher` | `Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcherInterface` |
  | `sylius.akeneo.reference_entity_attribute_type_matcher` | `Synolia\SyliusAkeneoPlugin\TypeMatcher\ReferenceEntityAttribute\ReferenceEntityAttributeTypeMatcherInterface` |
  | `sylius.akeneo.type_matcher.taxon.attribute` | `Synolia\SyliusAkeneoPlugin\TypeMatcher\TaxonAttribute\TaxonAttributeTypeMatcherInterface` |
* **BC BREAK**: Minimum requirements have been raised to PHP 8.3, Sylius 2.2 and Symfony 6.4 or 7.4.
  Symfony 7.0 to 7.3 are no longer supported.
* Symfony 8 (with Sylius 2.3 and PHP 8.4) and Doctrine DBAL 4 are now supported.
* **BC BREAK**: DBAL 4 removed the `array` type, so the following columns are now mapped as `json`:
  `akeneo_api_configuration_categories.notImportCategories`, `akeneo_api_configuration_categories.rootCategories`,
  `akeneo_api_configuration_product.attributeMapping`, `akeneo_api_product_filters_rules.locales`,
  `akeneo_api_product_filters_rules.excludeFamilies`, `akeneo_product_group.variationAxes`,
  `akeneo_product_group.associations`, `akeneo_taxon_attributes.configuration` and `akeneo_taxon_attribute_values.json_value`.
  * Run `bin/console doctrine:migrations:migrate`: migration `Version20261121000000` converts the existing serialized data to JSON.
  * If you override one of these entities or their mapping, use `Types::JSON` instead of `Types::ARRAY`.
* **BC BREAK**: Removed `Synolia\SyliusAkeneoPlugin\Checker\IsEnterpriseChecker` and `IsEnterpriseCheckerInterface`, deprecated since v3.1.0.
  Use `Synolia\SyliusAkeneoPlugin\Checker\EditionCheckerInterface::isEnterprise()` instead.

# UPGRADE FROM `v3.0.0` TO `v3.1.0`

* **BC BREAK**: Removed api configuration `is_enterprise` column. 
  Instead, we now use an Akeneo edition configuration for better granular checks.
  * To change the value, you have to update the configuration in page `/admin/akeneo/api/configuration`
  * In api_configuration fixture, you must use `edition` instead of `is_enterprise`. 
    Values are available in class `Synolia\SyliusAkeneoPlugin\Config\AkeneoEditionEnum`
  * If you are using `Synolia\SyliusAkeneoPlugin\Checker\IsEnterpriseCheckerInterface` in your project,
    and you run Akeneo Serenity, please note that it will return false.
    Please use Synolia\SyliusAkeneoPlugin\Checker\EditionCheckerInterface instead.
