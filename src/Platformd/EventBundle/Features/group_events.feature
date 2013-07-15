# Commented pending merge of site util changes to sort scoping problems
Feature: Group Events
    In order to control which Events show up on my group page
    As a user
    I need to be able to add and edit Events

#    Background:
#        Given I am authenticated as a user
#            And I am on the "Demo" site
#            And I have the following groups:
#                | name            | slug           | site  | category | description                  | public | featured | owner |
#                | Group 1         | group-1        | en    | topic    | this is group 1              | yes    | no       |       |
#            And I have the following events:
#                | name                  | slug                 | site  | start    | end     | details                | group   | private | active |
#                | Test group event      | test-group-event     | en    | +2 days  | +4 days | Some description       | Group 1 | 0       | 1      |
#                | Inactive group event  | inactive-group-event | en    | +2 days  | +4 days | Some description       | Group 1 | 0       | 0      |
#
#    Scenario: I can view an existing event
#        Given I am on "/events"
#        When I click on "Test group event"
#        Then I should be on the "group" event called "Test group event" on "en"
#
#    Scenario: I can create a group event
#        Given I am on "/groups/group-1"
#            And I add "event" for group "Group 1"
#            And I fill in the following:
#                | Title          | Group Event         |
#                | Event Details  | Group Event Details |
#            And I select the "Online Event" radio button
#            And I select the "Public Event" radio button
#            And I press "Create"
#        Then I should see "Your event has been successfully added." in the flash message
#            And I should be on the "group" event called "Group Event" on "en"
#
#    Scenario: I can create a new event if I do not have any groups
#        Given I am on "/events"
#        When I click on "create a new group"
#            And I fill in the following:
#                | Group Name   | Group for event        |
#            And I press "Create"
#        Then I should see "Your group was created. Fill in the details below to list your upcoming event." in the flash message
#        When I fill in the following:
#                | Title          | Group Event         |
#                | Event Details  | Group Event Details |
#            And I select the "Online Event" radio button
#            And I select the "Public Event" radio button
#            And I press "Create"
#        Then I should see "Your event has been successfully added." in the flash message
#            And I should be on the "group" event called "Group Event" on "en"
#
#    Scenario: I can edit a group event
#        Given I am on "/events"
#        When I click on "Test group event"
#            And I click on "Edit Event"
#            And I fill in the following:
#                | Title         | Group Event Updated  |
#                | Event Details | Description Updated |
#            And I press "Update"
#        Then I should see "Event has been saved successfully." in the flash message

# Commented out pending javascript testing
#    Scenario: I can cancel a group event
#        Given I am on "/events"
#        When I click on "Test group event"
#            And I click on "Edit Event"
#            And I press "Cancel Event"
#            And I press "Yes"
#        Then I should see "Event has been canceled successfully and attendees will be notified!" in the flash message

# Commented out pending javascript testing
#    Scenario: I can reactivate a group event
#        Given I am on "/events"
#        When I click on "Inactive group event"
#            And I click on "Edit Event"
#            And I press "Activate Event"
#            And I press "Yes"
#        Then I should see "Event has been canceled successfully and attendees will be notified!" in the flash message
