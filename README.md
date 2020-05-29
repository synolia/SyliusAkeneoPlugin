<p align="center">
    <a href="https://sylius.com" target="_blank">
        <img src="https://demo.sylius.com/assets/shop/img/logo.png" />
    </a>
</p>

<h1 align="center">Sylius Akeneo Plugin</h1>

<p align="center">This plugin allow you to import data from <a href="https://www.akeneo.com/" target="_blank">Akeneo PIM</a>.</p>

## Features

* Configure your Akeneo Account - [Documentation](docs/CONFIGURE.md)
* Configure which data should be imported and how it will be imported. - [Documentation](docs/CONFIGURE_DETAIL.md)
* Customize imports according to your business needs. - [Documentation](docs/CUSTOMIZE.md)
* Launch imports through Cli or Back-Office - [Documentation](docs/LAUNCH.md)

## Requirements

| | Version |
| :--- | :--- |
| PHP  | 7.3+ |
| Sylius | 1.7+ |
| Akeneo PIM  | 3.0+ |


## Installation

1. Add the bundle and dependencies in your composer.json :

    ```shell
    composer require synolia/sylius-akeneo-plugin
    ```
   
2. Enable the plugin in your `config/bundles.php` file by add
   
    ```php
    Synolia\SyliusAkeneoPlugin\SynoliaSyliusAkeneoPlugin::class => ['all' => true],
    ```
   
3. Import required config in your `config/packages/_sylius.yaml` file:
    
    ```yaml
    imports:
        - { resource: "@SynoliaSyliusAkeneoPlugin/Resources/config/config.yaml" }
    ```
   
4. Import routing in your `config/routes.yaml` file:

    ```yaml
    synolia_akeneo:
        resource: "@SynoliaSyliusAkeneoPlugin/Resources/config/routes.yaml"
        prefix: /admin
    ```
   
5. Copy plugin migrations to your migrations directory (e.g. `src/Migrations`) and apply them to your database:

    ```shell
    cp -R vendor/synolia/sylius-akeneo-plugin/Migrations/* src/Migrations
    bin/console doctrine:migrations:migrate
    ```

## Development

See [How to contribute](CONTRIBUTING.md).

## License

This library is under the MIT license.

## Credits

Developed by [Synolia](https://synolia.com/).
