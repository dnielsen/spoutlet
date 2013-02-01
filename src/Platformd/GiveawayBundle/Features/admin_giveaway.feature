Feature: Giveaway Admin
    In order to control which key giveaways show up on each site
    As an admin
    I need to be able to add, edit, and publish key giveaways

    Background:
        Given I am authenticated as an organizer
            And there is a game called "Skyrim"

    Scenario: Add a key giveaway
        Given I am on "/admin"
        When I click to add new "Giveaways"
            And I fill in the following:
                | Name          | keygiveaway name          |
                | Status        | active                    |
                | Giveaway Type | key_giveaway              |
                | Description   | content                   |
            And I attach the file "foo.jpg" to "Banner Image"
            And I check the "Demo" option for "Sites"
            And I select "Skyrim" from "Game"
            And I press "Save Giveaway"
        Then I should see "Giveaway has been saved"

    Scenario: Edit a key giveaway
        Given there is a key giveaway called "giveaway"
            And I am on the edit page for the key giveaway
        When I select "Skyrim" from "Game"
            And I fill in "Name" with "Updated name"
            And I press "Save Giveaway"
        Then I should see "Giveaway has been saved."
