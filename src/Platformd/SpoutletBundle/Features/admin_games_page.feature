Feature: Games Page Admin
    In order to feature special information about a game
    As a client
    I need to be able to add/edit/delete game/trailer pages

    Background:
        Given I am authenticated as a Dell Contact
            And I go to "/admin"
            And there is a game called "Starcraft"

    Scenario: Add a new games page
        When I click to add new "Game Pages"
        And I select "Starcraft" from Game
        And I fill in "About the Game" with "It's fun!"
        And I press "Create"
        Then I should see "game page was created"

    Scenario: List existing game pages
        Given there is a game page called "Starcraft"
        And there is a game page called "Warcraft"
        When I click on "Game Pages"
        Then I should see 2 data rows
        And I should see "Starcraft"

    Scenario: Edit existing game
        Given there is a game page called "Starcraft"
        When I click on "Game Pages"
        And I click on "Starcraft"
        And I fill in "About the Game" with "It's old!"
        Then I should see "game page was saved"

    Scenario: Preview the games page
        # todo