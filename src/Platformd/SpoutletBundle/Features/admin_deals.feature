Feature: Deal Admin
    In order to advertise and incentivize some product
    As an organizer
    I need to be able to add/edit/delete deals

    Background:
        Given I am authenticated as an organizer
            And I go to "/admin"
            And there is a game called "Diablo 3"

    Scenario: Add a new deal
        When I click to add new "Deals"
            And I select "Diablo 3" from "Game"
            And I fill in the following:
                | Name                      | Buy Diablo 3 and Get a Bonus Upgrade  |
                | URL string - /deal/{slug} | diablo-3-bonus                        |
                | Starts At                 | 2012-06-12 12:00                      |
                | Ends At                   | 2012-06-21 12:00                      |
                | Timezone                  | UTC                                   |
                | Description               | Lorem ipsum                           |
            And I check the "Demo" option for "Sites"
            And I select "published" from "Status"
            # missing image uploads
            # missing the gradient details
            And I press "Create"
        Then I should see "deal was created"

    Scenario: List existing deals
        Given there is a deal called "Buy Diablo 3 and Get a Bonus Upgrade" in "en"
            And there is a deal called "Free Swag" in "en"
            And there is a deal called "Deal for China!" in "zh"
        When I click on "Deals"
            And I click on "Demo"
        Then I should see 2 data rows
            And I should see "Free Swag"

    Scenario: Edit existing game
        Given there is a deal called "Free Swag" in "en"
        When I click on "Deals"
            And I click on "Demo"
            And I click on "Free Swag"
            And I fill in the following:
                | Name            | Updated!                         |
            And I press "Save"
        Then I should see "deal was saved"

    Scenario: Preview the deal
        Given there is a deal called "Free Swag" in "en"
        When I click on "Deals"
            And I click on "Demo"
            And I click on the URL for "Free Swag"
        Then I should be on the deal called "Free Swag" in "en"
