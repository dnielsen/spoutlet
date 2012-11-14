Feature: Event Admin
    In order to control which Events show up on each site
    As an admin
    I need to be able to add and edit Events

    Background:
        Given I am authenticated as an organizer
            And there is a game called "Skyrim"

    Scenario: I can create a new Event
        Given I am on "/admin"
        When I click to add new "Events"
            And I fill in the following:
                | Name  | My Events |
            And I select "Skyrim" from "Game"
            And I check the "Demo" option for "Sites"
            And I press "Save"
        Then I should see "Event has been saved"
