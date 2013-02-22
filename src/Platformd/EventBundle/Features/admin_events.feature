Feature: Event Admin
    In order to control which Events show up on each site
    As an admin
    I need to be able to add and edit Events

    Background:
        Given I am authenticated as an organizer
#        And I have the following events:
#                | name                | slug               | site  | start    | end     | details                |
#                | Test global event   | test-global-event  | en    | -4 days  | +4 days | Some description       |
#                | Other global event  | other-global-event | en    | -4 days  | +4 days | Some other description |
            And there is a game called "Skyrim"

#    Scenario: I can create a new event
#        Given I am on "/admin"
#        When I click to add new "Events"
#            And I fill in the following:
#                | Event Title   | Test Event        |
#                | Event Details | Description       |
#            And I select "Skyrim" from "Game"
#            And I check the "Demo" option for "Sites"
#            And I select the "groupEvent_online_1" radio button
#            And I select the "Public Event" radio button
#            And I press "Save"
#        Then I should see "New event posted successfully!"

#    Scenario: I can edit an event
#        Given I am on "/admin"
#        When I click to add new "Events"
#            And I fill in the following:
#                | Event Title   | Test Event Updated |
#                | Event Details | Description        |
#            And I select "Skyrim" from "Game"
#            And I check the "Demo" option for "Sites"
#            And I press "Save"
#        Then I should see "New event posted successfully!"
