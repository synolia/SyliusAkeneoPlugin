.DEFAULT_GOAL := help
SHELL=/bin/bash
COMPOSER_ROOT=composer
TEST_DIRECTORY=tests/Application
CONSOLE=cd tests/Application && php bin/console -e test
COMPOSER=cd tests/Application && composer
YARN=cd tests/Application && yarn

SYLIUS_VERSION=1.11.0
SYMFONY_VERSION=5.4
PHP_VERSION=8.0
PLUGIN_NAME=synolia/sylius-akeneo-plugin

###
### DEVELOPMENT
### ¯¯¯¯¯¯¯¯¯¯¯

install: sylius ## Install Plugin on Sylius [SyliusVersion=1.11] [SymfonyVersion=5.4] [PHP_VERSION=8.0]
.PHONY: install

reset: ## Remove dependencies
	rm -rf tests/Application
.PHONY: reset

phpunit: phpunit-configure phpunit-run ## Run PHPUnit
.PHONY: phpunit

###
### OTHER
### ¯¯¯¯¯¯

sylius: sylius-standard install-plugin update-dependencies install-sylius
.PHONY: sylius

sylius-standard:
	${COMPOSER_ROOT} create-project sylius/sylius-standard ${TEST_DIRECTORY} "~${SYLIUS_VERSION}" --no-install --no-scripts
	${COMPOSER} require sylius/sylius:"~${SYLIUS_VERSION}"

install-plugin:
	${COMPOSER} config repositories.plugin '{"type": "path", "url": "../../"}'
	${COMPOSER} config extra.symfony.allow-contrib true
	${COMPOSER} config minimum-stability "dev"
	${COMPOSER} config prefer-stable true
	${COMPOSER} req "${PLUGIN_NAME}:*" --prefer-source --no-scripts

	cp -r install/Application tests
	cp -r tests/data/* ${TEST_DIRECTORY}/

update-dependencies:
	${COMPOSER} config extra.symfony.require "^${SYMFONY_VERSION}"
	${COMPOSER} require --dev donatj/mock-webserver:^2.1 --no-scripts --no-update
# FIX since https://github.com/Sylius/Sylius/pull/13215 is not merged
	${COMPOSER} require doctrine/dbal:"^2.6" doctrine/orm:"^2.9" --no-scripts --no-update
ifeq ($(shell [[ $(SYMFONY_VERSION) == 4.4 && $(PHP_VERSION) == 7.4 ]] && echo true ),true)
	${COMPOSER} require sylius/admin-api-bundle:1.10.0 --no-scripts --no-update
endif
ifeq ($(SYLIUS_VERSION), 1.8.0)
	${COMPOSER} update --no-progress --no-scripts --prefer-dist -n
endif

	${COMPOSER} update --no-progress -n

install-sylius:
	${CONSOLE} d:d:c
	${CONSOLE} d:mig:mig -n
	${CONSOLE} syl:fix:load akeneo -n
	${YARN} install
	${YARN} build
	${CONSOLE} cache:clear

phpunit-configure:
	cp phpunit.xml.dist ${TEST_DIRECTORY}/phpunit.xml
	echo -e "\nMOCK_SERVER_HOST=localhost\nMOCK_SERVER_PORT=8987\n" >> ${TEST_DIRECTORY}/.env.test.local

phpunit-run:
	cd ${TEST_DIRECTORY} && ./vendor/bin/phpunit

behat-configure: ## Configure Behat
	(cd ${TEST_DIRECTORY} && cp behat.yml.dist behat.yml)
	(cd ${TEST_DIRECTORY} && sed -i "s#vendor/sylius/sylius/src/Sylius/Behat/Resources/config/suites.yml#vendor/${PLUGIN_NAME}/tests/Behat/Resources/suites.yml#g" behat.yml)
	(cd ${TEST_DIRECTORY} && sed -i "s#vendor/sylius/sylius/features#vendor/${PLUGIN_NAME}/features#g" behat.yml)
	(cd ${TEST_DIRECTORY} && sed -i '2i \ \ \ \ - { resource: "../vendor/${PLUGIN_NAME}/tests/Behat/Resources/services.xml\" }' config/services_test.yaml)

grumphp:
	vendor/bin/grumphp run

help: SHELL=/bin/bash
help: ## Dislay this help
	@IFS=$$'\n'; for line in `grep -h -E '^[a-zA-Z_#-]+:?.*?##.*$$' $(MAKEFILE_LIST)`; do if [ "$${line:0:2}" = "##" ]; then \
	echo $$line | awk 'BEGIN {FS = "## "}; {printf "\033[33m    %s\033[0m\n", $$2}'; else \
	echo $$line | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m%s\n", $$1, $$2}'; fi; \
	done; unset IFS;
.PHONY: help
