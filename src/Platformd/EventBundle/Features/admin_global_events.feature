Feature: Global Events
    In order to control which Events show up on each site
    As an admin
    I need to be able to add and edit Events

    Background:
        Given I am authenticated as an organizer
        And I have the following events:
                | name                  | slug                 | site  | start    | end     | details                |
                | Test global event     | test-global-event    | en    | +2 days  | +4 days | Some description       |
                | Other global event    | other-global-event   | en    | +2 days  | +4 days | Some other description |
            And there is a game called "Skyrim"

    Scenario: I can view an existing event
        Given I am on "/events"
        When I click on "Test global event"
        Then I should be on the "global" event called "Test global event" on "en"

    Scenario: I can create a new event
        Given I am on "/admin"
        When I click to add new "Events"
            And I fill in the following:
                | Title         | Test Event        |
                | Event Details | Description       |
            And I select "Skyrim" from "Game"
            And I check the "Demo" option for "Sites"
            And I select the "Online Event" radio button
            And I press "Save"
        Then I should see "New event posted successfully!"

    Scenario: I can edit an event
        Given I am on "/admin"
        When I click on "Events"
            And I click on "Test global event"
            And I fill in the following:
                | Title         | Test Event Updated  |
                | Event Details | Description Updated |
            And I press "Save"
        Then I should see "Event saved successfully"

    Scenario: List existing contests for admins
        Given I am on "/admin"
        When I click on "Events"
        Then I should see 2 data rows
            And I should see "Test global event"
            And I should see "Other global event"
