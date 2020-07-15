@akeneo_import_categories @cli
Feature: Import categories from Akeneo
    In order to have categories in my store
    As a Developer
    I want to import categories from Akeneo

    Scenario: Import categories without config
        When I run akeneo import categories command
        Then I should get an exception "The API is not configured in the admin section."
