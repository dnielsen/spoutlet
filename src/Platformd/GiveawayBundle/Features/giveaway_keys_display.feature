Feature: Giveaway keys display
    In order to control which giveways can display remaining keys
    As an admin
    I need to be able to check visibility of keys

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
                | Content       | content                   |
            And I attach the file "foo.jpg" to "Banner Image"
            And I check the "Demo" option for "Sites"
            And I select "Skyrim" from "Game"
            And I check "Show key count"
            And I select "Active" from "Status"
            And I press "Save Giveaway"
        Then I should see "Giveaway has been saved"
        And Giveway "keygiveaway name" should display remaining keys

    Scenario: Add a key giveaway with non displayed key count
        Given I am on "/admin"
        When I click to add new "Giveaways"
            And I fill in the following:
                | Name          | keygiveaway name          |
                | Status        | active                    |
                | Giveaway Type | key_giveaway              |
                | Content       | content                   |
            And I attach the file "foo.jpg" to "Banner Image"
            And I check the "Demo" option for "Sites"
            And I select "Skyrim" from "Game"
            And I uncheck "Show key count"
            And I press "Save Giveaway"
        Then I should see "Giveaway has been saved"
        And Giveway "keygiveaway name" should not display remaining keys
