# This file was rushed - very under pressure with a deadline that absolutely had to be met... this needs cleaned up asap (by cleanup I mean moving stuff out of background that doesn't apply to all scenarios... also cleaning up the working significantly)

Feature: Deal Pool
    In order to advertise and incentivize some product
    As an organizer
    I need to be able to add/edit/delete deals with key pools

    Background:
        Given I am authenticated as an organizer
            And I have the following users:
                | username      | email                   |  cevo id |
                | William       | William@example.com     |  1       |
                | Harry         | Harry@example.com       |  2       |
                | Charles       | Charles@example.com     |  3       |
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
            And I check the "Global" option for "Sites"
            And I select "published" from "Status"
            And I press "Create"

            And I go to "/admin/deal/list/1"
            And I click on "Manage pools"
            And I click on "Create New Pool"
            And I attach the file "dev/sample-keys/not_valid_sample_keys.csv" to "Keysfile"
            And I uncheck "Isactive"
            And I select "United States" from "Eligible Countries"
            And I press "Save Pool"

            And I go to "/admin/deal/list/1"
            And I click on "Manage pools"
            And I click on "Create New Pool"
            And I attach the file "dev/sample-keys/not_valid_sample_keys.csv" to "Keysfile"
            And I uncheck "Isactive"
            And I select "United Kingdom" from "Eligible Countries"
            And I press "Save Pool"

            And I go to "/admin/deal/list/1"
            And I click on "Manage pools"
            And I click on "Create New Pool"
            And I attach the file "dev/sample-keys/gb_only_sample_keys.csv" to "Keysfile"
            And I check "Isactive"
            And I select "United Kingdom" from "Eligible Countries"
            And I press "Save Pool"

            And I go to "/admin/deal/list/1"
            And I click on "Manage pools"
            And I click on "Create New Pool"
            And I attach the file "dev/sample-keys/us_only_sample_keys.csv" to "Keysfile"
            And I check "Isactive"
            And I select "United States" from "Eligible Countries"
            And I press "Save Pool"

            And I go to "/admin/deal/list/1"
            And I click on "Manage pools"
            And I click on "Create New Pool"
            And I attach the file "dev/sample-keys/gb_only_sample_keys_2.csv" to "Keysfile"
            And I check "Isactive"
            And I select "United Kingdom" from "Eligible Countries"
            And I press "Save Pool"

            And I go to "/admin/deal/list/1"
            And I click on "Manage pools"
            And I click on "Create New Pool"
            And I attach the file "dev/sample-keys/ie_only_sample_keys.csv" to "Keysfile"
            And I check "Isactive"
            And I fill in "1" for "Maxkeysperip"
            And I select "Ireland" from "Eligible Countries"
            And I press "Save Pool"

    Scenario: I should be told that there are no more keys if I join the queue and the keys run out before I get one - but I should only see the message once
        Given I am authenticated as a user
            And I am located in "GB"
            And I go to "/deal/diablo-3-bonus"
            And I click "deal-redeem-link"
            And I should see "You're in the queue"
            And the keys run out for the "Buy Diablo 3 and Get a Bonus Upgrade" deal
        When The Key Queue Processor is run
            And I go to "/deal/diablo-3-bonus"
        Then I should see "no longer any keys available"
            And I go to "/deal/diablo-3-bonus"
            And I should not see "no longer any keys available"

    Scenario: I am a user from the GB I should get a valid GB key
        Given I am authenticated as a user
            And I am located in "GB"
            And I go to "/deal/diablo-3-bonus"
            And I click "deal-redeem-link"
            And I should see "You're in the queue"
        When The Key Queue Processor is run
            And I go to "/deal/diablo-3-bonus"
        Then I should see "GB_only_1"

    Scenario: I am a user from the US I should get a valid US key
        Given I am authenticated as a user
            And I am located in "US"
            And I go to "/deal/diablo-3-bonus"
            And I click "deal-redeem-link"
            And I should see "You're in the queue"
        When The Key Queue Processor is run
            And I go to "/deal/diablo-3-bonus"
        Then I should see "US_only_1"

    Scenario: I am a user from a country that doesn't have any keys available to it
        Given I am authenticated as a user
            And I am located in "JP"
            And I go to "/deal/diablo-3-bonus"
            And I click "deal-redeem-link"
            And I should see "You're in the queue"
        When The Key Queue Processor is run
            And I go to "/deal/diablo-3-bonus"
        Then I should see "You are not eligible (based on your age and/or country)"

    Scenario: I am a user who was rejected for a key, but then moved country and successfully got key
        Given I am authenticated as a user
            And I am located in "JP"
            And I go to "/deal/diablo-3-bonus"
            And I click "deal-redeem-link"
            And I should see "You're in the queue"
            And The Key Queue Processor is run
            And I go to "/deal/diablo-3-bonus"
            And I should see "You are not eligible (based on your age and/or country)"
        When I am located in "GB"
            And I click "deal-redeem-link"
            And I should see "You're in the queue"
            And The Key Queue Processor is run
            And I go to "/deal/diablo-3-bonus"
        Then I should see "GB_only_1"

    Scenario: I am the third user from the GB I should get the third valid GB key
        Given I re-login as the user "William"
            And I am located in "GB"
            And I go to "/deal/diablo-3-bonus"
            And I click "deal-redeem-link"
            And The Key Queue Processor is run
            And I go to "/deal/diablo-3-bonus"
            And I should see "GB_only_1"
            And I re-login as the user "Harry"
            And I go to "/deal/diablo-3-bonus"
            And I click "deal-redeem-link"
            And The Key Queue Processor is run
            And I go to "/deal/diablo-3-bonus"
            And I should see "GB_only_2"
        When I re-login as the user "Charles"
            And I go to "/deal/diablo-3-bonus"
            And I click "deal-redeem-link"
            And The Key Queue Processor is run
            And I go to "/deal/diablo-3-bonus"
        Then I should see "GB_only_3"

    Scenario: The same IP address should not be able to claim more than 1 IP addresses
        Given I re-login as the user "William"
            And I am located in "IE"
            And I go to "/deal/diablo-3-bonus"
            And I click "deal-redeem-link"
            And The Key Queue Processor is run
            And I go to "/deal/diablo-3-bonus"
            And I should see "IE_only_1"
        When I re-login as the user "Harry"
            And I am located in "IE"
            And I go to "/deal/diablo-3-bonus"
            And I click "deal-redeem-link"
            And The Key Queue Processor is run
            And I go to "/deal/diablo-3-bonus"
        Then I should see "Sorry, a key could not be assigned to you as your IP address has already claimed the maximum number of keys allowed."

