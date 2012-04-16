Feature: Machine Code Giveaway
    In order to encourage people to buy Dell computers
    As a user
    I can enter my machine code and have a key giveaway approved

    Background:
        Given the following giveaway:
            | name             | type                | keys   |
            | Machine Giveaway | machine_code_submit | 123456 |

  Scenario: I can submit my machine code
    Given I am authenticated
    When I go to "/giveaways/machine-giveaway"
     And I fill in "Code" with "abcd1234"
     And I press "Submit Code"
    Then I should see "Your code has been submitted"
     And there should be a "pending" machine code entry in the database
     When I go to "/account/profile/giveaways"
     Then I should see "Machine Giveaway"
      And I should see "pending"

  Scenario: I can see my giveaway key after being approved
    Given I am authenticated
     And I have a "pending" machine code entry in the database
     And my machine code entry is approved
    When I go to "/account/profile/giveaways"
     Then I should see "Machine Giveaway"
     And I should see "123456"