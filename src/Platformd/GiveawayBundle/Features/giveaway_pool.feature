Feature: Giveaway Pool
    In order to participate in a giveaway
    As a user
    I need to be able to request a key from a giveaway for which I am eligible

    Background:
        Given I am authenticated as an organizer
            And I have the following users:
                | username      | email                   |  cevo id |
                | William       | William@example.com     |  1       |
                | Harry         | Harry@example.com       |  2       |
                | Charles       | Charles@example.com     |  3       |
            And I go to "/admin"
            And there is a game called "Diablo 3"
            And I click to add new "Giveaways"
            And I select "Diablo 3" from "Game"
            And I fill in the following:
                | Name                      | Diablo 3 Giveaway  |
                | URL                       | diablo-3-giveaway  |
                | Description               | Lorem ipsum        |
            And I check the "Demo" option for "Sites"
            And I select "active" from "Status"
            And I press "Save Giveaway"
            And I set the current giveaway to "Diablo 3 Giveaway"
            And the current giveaway has the following pools:
                | description | key_count | country | active | max_per_ip |
                | NOT_VALID   | 2         | US      | no     |            |
                | NOT_VALID_2 | 2         | GB      | no     |            |
                | GB_only     | 2         | GB      | yes    |            |
                | US_only     | 2         | US      | yes    |            |
                | GB_only_2   | 2         | GB      | yes    |            |
                | IE_only     | 2         | IE      | yes    | 1          |

    #Scenario: I should be told that there are no more keys if I join the queue and the keys run out before I get one - but I should only see the message once
    #    Given I am authenticated as a user
    #        And I am located in "GB"
    #        And I go to "/giveaways/diablo-3-giveaway"
    #        And I click "GET KEY"
    #        And I should see "You're in the queue"
    #        And the keys run out for the current giveaway
    #    When The Key Queue Processor is run
    #        And I go to "/giveaways/diablo-3-giveaway"
    #    Then I should see "no longer any keys available"
    #        And I go to "/giveaways/diablo-3-giveaway"
    #        And I should not see "no longer any keys available"

    Scenario: I am a user from the GB I should get a valid GB key
        Given I am authenticated as a user
            And I am located in "GB"
            And I go to "/giveaways/diablo-3-giveaway"
            And I click "GET KEY"
            And I should see "You're in the queue"
        When The Key Queue Processor is run
            And I go to "/giveaways/diablo-3-giveaway"
        Then I should see "GB_only_1"

    Scenario: I am a user from the US I should get a valid US key
        Given I am authenticated as a user
            And I am located in "US"
            And I go to "/giveaways/diablo-3-giveaway"
            And I click "GET KEY"
            And I should see "You're in the queue"
        When The Key Queue Processor is run
            And I go to "/giveaways/diablo-3-giveaway"
        Then I should see "US_only_1"

    Scenario: I am a user from a country that doesn't have any keys available to it
        Given I am authenticated as a user
            And I am located in "JP"
            And I go to "/giveaways/diablo-3-giveaway"
        Then I should not see "GET KEY"

    Scenario: I am the third user from the GB I should get the third valid GB key, and it should be taken from the second valid keypool if the first valid keypool is empty
        Given I re-login as the user "William"
            And I am located in "GB"
            And I go to "/giveaways/diablo-3-giveaway"
            And I click "GET KEY"
            And The Key Queue Processor is run
            And I go to "/giveaways/diablo-3-giveaway"
            And I should see "GB_only_1"
            And I re-login as the user "Harry"
            And I go to "/giveaways/diablo-3-giveaway"
            And I click "GET KEY"
            And The Key Queue Processor is run
            And I go to "/giveaways/diablo-3-giveaway"
            And I should see "GB_only_2"
        When I re-login as the user "Charles"
            And I go to "/giveaways/diablo-3-giveaway"
            And I click "GET KEY"
            And The Key Queue Processor is run
            And I go to "/giveaways/diablo-3-giveaway"
        Then I should see "GB_only_2_1"

    Scenario: The same IP address should not be able to claim more than 1 IP addresses
        Given I re-login as the user "William"
            And I am located in "IE"
            And I go to "/giveaways/diablo-3-giveaway"
            And I click "GET KEY"
            And The Key Queue Processor is run
            And I go to "/giveaways/diablo-3-giveaway"
            And I should see "IE_only_1"
        When I re-login as the user "Harry"
            And I am located in "IE"
            And I go to "/giveaways/diablo-3-giveaway"
            And I click "GET KEY"
            And The Key Queue Processor is run
            And I go to "/giveaways/diablo-3-giveaway"
        Then I should see "Sorry, a key could not be assigned to you as your IP address has already claimed the maximum number of keys allowed."
