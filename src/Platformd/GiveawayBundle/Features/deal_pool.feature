# This file was rushed - very under pressure with a deadline that absolutely had to be met... this needs cleaned up asap (by cleanup I mean moving stuff out of background that doesn't apply to all scenarios... also cleaning up the working significantly)

Feature: Deal Pool
    In order to advertise and incentivize some product
    As an organizer
    I need to be able to add/edit/delete deals with key pools

    Background:
        Given I am authenticated as an organizer
            And I have the following users:
                | username      | email                   | cevo country | cevo id |
                | William       | William@example.com     | UK           | 1       |
                | Harry         | Harry@example.com       | UK           | 2       |
                | Charles       | Charles@example.com     | UK           | 3       |
                | Peter         | Peter@example.com       | IE           | 4       |
                | Paul          | Paul@example.com        | IE           | 5       |
                | CaptAmerica   | CaptAmerica@example.com | US           | 6       |
                | MrJapan       | MrJapan@example.com     | JP           | 7       |
                | UnknownMan    | UnknownMan@example.com  | UNKNOWN      | 8       |
            And I go to "/admin"
            And there is a game called "Diablo 3"
            And I click to add new "Deals"
            And I select "Diablo 3" from "Game"
            And I fill in the following:
                | Name                      | Buy Diablo 3 and Get a Bonus Upgrade  |
                | URL string - /deal/{slug} | diablo-3-bonus                        |
                | Starts At                 | 2000-01-01 00:00                      |
                | Ends At                   | 2099-01-01 00:00                      |
                | Timezone                  | UTC                                   |
                | Description               | Lorem ipsum                           |
            And I check the "Demo" option for "Sites"
            And I select "published" from "Status"
            And I press "Create"

            And I go to "/admin/deal/list/1"
            And I click on "Manage pools"
            And I click on "Create New Pool"
            And I attach the file "/home/ubuntu/sites/alienwarearena.com/dev/sample-keys/ie_only_sample_keys.csv" to "Keysfile"
            And I check "Isactive"
            And I fill in "1" for "Maxkeysperip"
            And I select "Ireland" from "Eligible Countries"
            And I press "Save Pool"

            And I go to "/admin/deal/list/1"
            And I click on "Manage pools"
            And I click on "Create New Pool"
            And I attach the file "/home/ubuntu/sites/alienwarearena.com/dev/sample-keys/not_valid_sample_keys.csv" to "Keysfile"
            And I uncheck "Isactive"
            And I select "United States" from "Eligible Countries"
            And I press "Save Pool"

            And I go to "/admin/deal/list/1"
            And I click on "Manage pools"
            And I click on "Create New Pool"
            And I attach the file "/home/ubuntu/sites/alienwarearena.com/dev/sample-keys/not_valid_sample_keys.csv" to "Keysfile"
            And I uncheck "Isactive"
            And I select "United Kingdom" from "Eligible Countries"
            And I press "Save Pool"

            And I go to "/admin/deal/list/1"
            And I click on "Manage pools"
            And I click on "Create New Pool"
            And I attach the file "/home/ubuntu/sites/alienwarearena.com/dev/sample-keys/uk_only_sample_keys.csv" to "Keysfile"
            And I check "Isactive"
            And I select "United Kingdom" from "Eligible Countries"
            And I press "Save Pool"

            And I go to "/admin/deal/list/1"
            And I click on "Manage pools"
            And I click on "Create New Pool"
            And I attach the file "/home/ubuntu/sites/alienwarearena.com/dev/sample-keys/us_only_sample_keys.csv" to "Keysfile"
            And I check "Isactive"
            And I select "United States" from "Eligible Countries"
            And I press "Save Pool"

            And I go to "/admin/deal/list/1"
            And I click on "Manage pools"
            And I click on "Create New Pool"
            And I attach the file "/home/ubuntu/sites/alienwarearena.com/dev/sample-keys/uk_only_sample_keys_2.csv" to "Keysfile"
            And I check "Isactive"
            And I select "United Kingdom" from "Eligible Countries"
            And I press "Save Pool"

    Scenario: I am a user from the UK I should get a valid UK key
        Given I re-login as the user "William"
        When I go to "/deal/diablo-3-bonus"
            And I click "deal-redeem-link"
        Then I should see "UK_only_1"

    Scenario: I am a user from the US I should get a valid US key
        Given I re-login as the user "CaptAmerica"
        When I go to "/deal/diablo-3-bonus"
            And I click "deal-redeem-link"
        Then I should see "US_only_1"

    Scenario: I am a user from an unknown country
        Given I re-login as the user "UnknownMan"
        When I go to "/deal/diablo-3-bonus"
            And I click "deal-redeem-link"
        Then I should see "Invalid country selection."

    Scenario: I am a user from a country that doesn't have any keys available to it
        Given I re-login as the user "MrJapan"
        When I go to "/deal/diablo-3-bonus"
            And I click "deal-redeem-link"
        Then I should see "Sorry! This offer is not available at your location."

    Scenario: I am a user the second user from the UK I should get the second valid UK key
        Given I re-login as the user "William"
            And I go to "/deal/diablo-3-bonus"
            And I click "deal-redeem-link"
            And I should see "UK_only_1"
            And I re-login as the user "Harry"
            And I go to "/deal/diablo-3-bonus"
            And I click "deal-redeem-link"
            And I should see "UK_only_2"
            And I re-login as the user "Charles"
            And I go to "/deal/diablo-3-bonus"
            And I click "deal-redeem-link"
            And I should see "UK_only_3"

    Scenario: The same IP address should not be able to claim more than 2 IP addresses
        Given I re-login as the user "Peter"
            And I go to "/deal/diablo-3-bonus"
            And I click "deal-redeem-link"
            And I should see "IE_only_1"
            And I re-login as the user "Paul"
            And I go to "/deal/diablo-3-bonus"
            And I click "deal-redeem-link"
            And I should see "Your IP address is not allowed to redeem any more deals."

