@managing_api_configuration
Feature: Adding configuration to Akeneo API
    In order to use Akeneo API
    As an Administrator
    I want to configure Akeneo API

    Background:
        Given the store operates on a channel named "Web-USD" in "USD" currency
        And I am logged in as an administrator

    @ui
    Scenario: Configure Akeneo API credentials
        Given I want to configure the akeneo api
        And I save my changes
        Then I should be notified that fields cannot be blank
