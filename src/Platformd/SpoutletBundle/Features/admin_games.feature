Feature: Games Admin
    In order to manage which games are in the system
    As an admin user
    I need to be able to add/edit/delete games

    Background:
        Given I am authenticated as a Dell Contact
            And I go to "/admin"

    Scenario: Add a new game
        When I click to add new "Games"
            And I fill in "Game Name" with "Warcraft3"
            And I select "RPG" from "Genre"
            And I press "Create"
        Then I should see "game was created"

    Scenario: List existing games
        Given there is a game called "Starcraft"
            And there is a game called "Warcraft"
        When I click on "Games"
        Then I should see 2 data rows
            And I should see "Starcraft"

    Scenario: Edit existing game
        Given there is a game called "Starcraft"
        When I click on "Games"
            And I click on "Starcraft"
            And I fill in "Game Name" with "Starcraft2"
            And I press "Save"
        Then I should see "game was saved"