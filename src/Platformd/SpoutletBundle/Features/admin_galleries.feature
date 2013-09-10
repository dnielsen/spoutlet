Feature: Gallery Admin
    In order to showcase media for users
    As an admin
    I need to be able to add/edit/delete galleries

    Background:
        Given I am authenticated as an admin
            And I go to "/admin"

    Scenario: Add a new gallery
        When I click "Add new gallery"
            And I fill in "Test Gallery" for "Gallery name"
            And I check the "image" option for "Enabled for"
            And I check the "Demo" option for "Sites"
            And I select "Published" from "Status"
            And I press "Create"
        Then I should see "The gallery was created!"

    Scenario: List existing galleries
        Given there is a gallery called "Demo Test Gallery" in "en"
            And there is a gallery called "Demo Gal 2" in "en"
            And there is a gallery called "NA Gallery" in "en_US"
        When I click on "Gallery Management"
            And I click on "Demo"
        Then I should see 2 data rows
            And I should see "Demo Test Gallery"
            And I should see "Demo Gal 2"
            And I should not see "NA Gallery"

    Scenario: Edit existing gallery
        Given there is a gallery called "Demo Test Gallery" in "en"
        When I click on "Gallery Management"
            And I click on "Demo"
            And I click on "Demo Test Gallery"
            And I fill in the following:
                | Gallery name  | Demo Test Gallery Updated! |
            And I press "Save"
        Then I should see "The gallery was saved!"
