# UPGRADE FROM `v3.0.0` TO `XXX`

* **BC BREAK**: Removed api configuration `is_enterprise` column. 
  Instead, we now use an Akeneo edition configuration for better granular checks.
  * To change the value, you have to update the configuration in page `/admin/akeneo/api/configuration`
  * In api_configuration fixture, you must use `edition` instead of `is_enterprise`. 
    Values are available in class `Synolia\SyliusAkeneoPlugin\Config\AkeneoEditionEnum`
