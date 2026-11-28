## Installation

The plugin is developed and tested against the official [Sylius Test Application](https://github.com/Sylius/TestApplication)
(`sylius/test-application`), installed as a Composer dev dependency. The plugin-specific configuration
of the test application (entity overrides, test services, fixtures) lives in `tests/TestApplication/`.

To be able to setup the plugin database, remember to configure your database credentials
in `tests/TestApplication/.env.local` and `tests/TestApplication/.env.test.local`
(default: `mysql://root@127.0.0.1/synolia_akeneo_plugin_%kernel.environment%`).

From the plugin root directory, run the following commands:

```bash
make install    # optionally: make install SYLIUS_VERSION=2.3.0 SYMFONY_VERSION=8.1
make phpunit
make serve      # Symfony local web server on the test application
```

`make install` installs the Composer dependencies and the frontend assets of the test application,
then creates the database, runs the migrations and loads the `default` fixtures suite (`test` environment by default).

To reset the test environment:

```bash
make reset
```

## Usage

### Running code analyse and tests

- GrumPHP (see configuration [grumphp.yml](grumphp.yml).)

  GrumPHP is executed by the Git pre-commit hook, but you can launch it manually with:

  ```bash
  make grumphp
  ```

- PHPUnit

  The tests use a mocked Akeneo API ([donatj/mock-webserver](https://github.com/donatj/mock-webserver)),
  configured in `tests/TestApplication/.env.test`.

  ```bash
  make phpunit
  ```

Use `vendor/bin/console` to run Symfony commands against the test application.
