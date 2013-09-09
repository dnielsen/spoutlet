Feature: Gallery Frontend
    In order to view media on the site
    As a user
    I need to be able to view galleries

    Background:
        Given I am authenticated as a user

    Scenario: View existing gallery
        Given there is a gallery called "Global Test Gallery" in "en"
            And I am on the "Global" site
        When I go to "/galleries"
        Then the "#media-filter-options" element should contain "Global Test Gallery"
