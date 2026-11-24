# UPGRADE FROM `v5.0.0-rc1` TO `v5.0.0`

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

# UPGRADE FROM `v3.0.0` TO `v3.1.0`

* **BC BREAK**: Removed api configuration `is_enterprise` column. 
  Instead, we now use an Akeneo edition configuration for better granular checks.
  * To change the value, you have to update the configuration in page `/admin/akeneo/api/configuration`
  * In api_configuration fixture, you must use `edition` instead of `is_enterprise`. 
    Values are available in class `Synolia\SyliusAkeneoPlugin\Config\AkeneoEditionEnum`
  * If you are using `Synolia\SyliusAkeneoPlugin\Checker\IsEnterpriseCheckerInterface` in your project,
    and you run Akeneo Serenity, please note that it will return false.
    Please use Synolia\SyliusAkeneoPlugin\Checker\EditionCheckerInterface instead.
