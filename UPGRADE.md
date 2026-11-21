# UPGRADE FROM `v3.0.0` TO `XXX`

* **BC BREAK**: Minimum requirements have been raised to PHP 8.3, Sylius 2.2 and Symfony 6.4 or 7.4.
  Symfony 7.0 to 7.3 are no longer supported.
* **BC BREAK**: Removed api configuration `is_enterprise` column. 
  Instead, we now use an Akeneo edition configuration for better granular checks.
  * To change the value, you have to update the configuration in page `/admin/akeneo/api/configuration`
  * In api_configuration fixture, you must use `edition` instead of `is_enterprise`. 
    Values are available in class `Synolia\SyliusAkeneoPlugin\Config\AkeneoEditionEnum`
  * If you are using `Synolia\SyliusAkeneoPlugin\Checker\IsEnterpriseCheckerInterface` in your project,
    and you run Akeneo Serenity, please note that it will return false.
    Please use Synolia\SyliusAkeneoPlugin\Checker\EditionCheckerInterface instead.
