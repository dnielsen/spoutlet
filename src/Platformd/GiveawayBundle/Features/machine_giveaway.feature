Feature: Machine Code Giveaway
    In order to encourage people to buy Dell computers
    As a user
    I can enter my machine code and have a key giveaway approved

    Background:
        Given I am authenticated
            And the following giveaway:
                | name             | type                | keys   |
                | Machine Giveaway | machine_code_submit | 123456 |

    Scenario: I can submit my machine code
        Given I am on "/giveaways/machine-giveaway"
        When I fill in "System Tag" with "abcd1234"
            And I press "Apply"
        Then I should see "Thanks for your participation"
            And there should be "1" "pending" machine code entry in the database
            And I should not see "System Tag"
            # try refreshing the page, still not there
            When I reload the page
            Then I should not see "System Tag"
        When I go to "/account/profile/giveaways"
            Then I should see "Machine Giveaway"
                And I should see "Pending"

    Scenario: I can see my giveaway key after being approved
        Given I have a "pending" machine code entry in the database
            And my machine code entry is approved
        When I go to "/account/profile/giveaways"
        Then I should see "Machine Giveaway"
            And I should see "123456"
