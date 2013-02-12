Feature: Contest Frontend
    In order to participate in site contests
    As an organizer
    I need to be able to view/enter contests

    Background:
        Given I am authenticated as an organizer
            And I have the following contests:
                | name                   | slug              | site  | submission_start | submission_end | voting_start | voting_end | category | max_entries | status    |
                | Diablo 3 Image Contest | diablo-3          | en    | -4 days          | +4 days        | -4 days      | +4 days    | image    | 0           | published |
                | Other Contest          | other             | en    | -4 days          | +4 days        | -4 days      | +4 days    | image    | 0           | published |
                | NA Contest             | na-contest        | en_US | -4 days          | +4 days        | -4 days      | +4 days    | image    | 0           | published |
                | Expired Contest        | expired-contest   | en    | -4 days          | -2 days        | -4 days      | -2 days    | image    | 0           | published |
                | Unstarted Contest      | unstarted-contest | en    | +4 days          | +5 days        | +4 days      | +5 days    | image    | 0           | published |
            And I am on the "Demo" site

    Scenario: List existing contests for the correct site
        Given I am on "/contests/image"
        Then I should see "Diablo 3 Image Contest"
            And I should see "Other Contest"
            And I should not see "NA Contest"

    Scenario: View contest page
        Given I am on "/contests/image"
        When I click "Diablo 3 Image Contest"
        Then I should be on "/contest/diablo-3"
            And I should see "Diablo 3 Image Contest"

    Scenario: Able to enter current contest
        Given I am on "/contests/image"
        When I click "Diablo 3 Image Contest"
        Then I should be on "/contest/diablo-3"
            And I should see "Diablo 3 Image Contest"
            And I should see "I have read and agreed to the Contest Rules"

    Scenario: Unable to enter an expired contest
        Given I am on "/contest/expired-contest"
            Then I should see "Expired Contest"
            And I should see "Submissions are no longer being accepted."

    Scenario: Unable to enter an unstarted contest
        Given I am on "/contests/image"
        When I click "Unstarted Contest"
        Then I should be on "/contest/unstarted-contest"
            And I should see "Unstarted Contest"
            And I should not see "I have read and agreed to the Contest Rules"

    Scenario: Expired contests should not show up on contest index page
        Given I am on "/contests/image"
        Then I should not see "Expired Contest"
