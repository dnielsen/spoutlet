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
            And I select "Starcraft" from "Game"
            And I fill in the following:
                | About the Game            | It's fun!                         |
                | External URL              | http://www.example.com            |
                | Recommended Laptop URL    | http://www.example.com/laptop     |
                | Recommended Desktop URL   | http://www.example.com/desktop    |
            And I check the "Demo" option for "Sites"
            And I select "published" from "Status"
            And I press "Create"
        Then I should see "game page was created"

    Scenario: List existing game pages
        Given there is a game page for "Starcraft" in "en"
            And there is a game page for "Warcraft" in "en"
            And there is a game page for "Battlegrounds" in "zh"
        When I click on "Game Pages"
            And I click on "Demo"
        Then I should see 2 data rows
            And I should see "Starcraft"

    Scenario: Edit existing game
        Given there is a game page for "Starcraft" in "en"
        When I click on "Game Pages"
            And I click on "Demo"
            And I click on "Starcraft"
            And I fill in the following:
                | About the Game            | It's old!                         |
                | External URL              | http://www.example.com/sc         |
                | Recommended Laptop URL    | http://www.example.com/laptop     |
                | Recommended Desktop URL   | http://www.example.com/desktop    |
            And I press "Save"
        Then I should see "game page was saved"

    Scenario: Preview the games page
        Given there is a game page for "Starcraft" in "en"
        And I have verified my age
        When I click on "Game Pages"
            And I click on "Demo"
            And I click on the URL for "Starcraft"
        Then I should be on the game page for "Starcraft" in "en"
