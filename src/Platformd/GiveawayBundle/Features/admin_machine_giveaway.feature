Feature: Machine Code Giveaway Admin
    In order to approve people's machine codes
    As an admin
    I can export and approve people's submissions

    Background:
        Given the following giveaway:
            | name             | type                | keys        |
            | Machine Giveaway | machine_code_submit | 123456,7890 |

        Given I have an account
            And I have the "ROLE_ORGANIZER" role
            And I am authenticated

        Given I have the following users:
            | username | email         |
            | user1    | user@foo.com  |
            | japan    | japan@ja.com  |
            | china    | china@zh.com  |

        Given the following machine code entries:
            | machineCode | username |
            | abcdef      | user1    |
            | ghijkl      | japan    |
            | 1sdfsdf     | china    |

    Scenario: I can approve key giveaway requests
        Given I am on "/admin"
        When I follow "Giveaways"
            And I follow "Global"
            And I follow "Approve System Tags"
            And I fill in "Emails" with "user@foo.com, japan@ja.com"
            And I press "Approve Tags"
        Then I should see "2 codes were approved"
            And there should be "2" "approved" machine code entry in the database

    Scenario: I will eventually run out of keys
        Given I am on "/admin"
        When I follow "Giveaways"
            And I follow "Global"
            And I follow "Approve System Tags"
            And I fill in "Emails" with "user@foo.com, japan@ja.com, china@zh.com"
            And I press "Approve Tags"
        Then I should see "There are no more unassigned giveaway keys"
            # we should still have 2 approved machine code entries
            And there should be "2" "approved" machine code entry in the database

    Scenario: Hey! That's not a real user
        Given I am on "/admin"
        When I follow "Giveaways"
            And I follow "Global"
            And I follow "Approve System Tags"
            And I fill in "Emails" with "foo@foo.com"
            And I press "Approve Tags"
        Then I should see "No user with email"
