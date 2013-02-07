Feature: Contest Frontend
    In order to participate in site contests
    As an organizer
    I need to be able to view/enter contests

    Background:
        Given I am authenticated as an organizer

    Scenario: List existing contests for the correct site
        Given there is a contest called "Diablo 3 Image Contest" in "en"
            And there is a contest called "Other Contest" in "en"
            And there is a contest called "NA Contest" in "en_US"
            And I am on the "Demo" site
        When I go to "/contests/image"
        Then I should see "Diablo 3 Image Contest"
            And I should see "Other Contest"
            And I should not see "NA Contest"

    Scenario: View contest page
        Given there is a contest called "Diablo 3 Image Contest" in "en"
            And I am on the "Demo" site
        When I go to "/contests/image"
            And I click "Diablo 3 Image Contest"
        Then I should be on "/contest/diablo-3-image-contest"
            And I should see "Diablo 3 Image Contest"

    Scenario: Able to enter current contest
        Given there is a contest called "Diablo 3 Image Contest" in "en"
            And I am on the "Demo" site
        When I go to "/contests/image"
            And I click "Diablo 3 Image Contest"
        Then I should be on "/contest/diablo-3-image-contest"
            And I should see "Diablo 3 Image Contest"
            And I should see "I have read and agreed to the Contest Rules"

    Scenario: Unable to enter an expired contest
        Given there is an expired contest called "Diablo 3 Image Contest" in "en"
            And I am on the "Demo" site
        When I go to "/contest/diablo-3-image-contest"
            Then I should see "Diablo 3 Image Contest"
            And I should see "Submissions are no longer being accepted."

    Scenario: Unable to enter an unstarted contest
        Given there is an unstarted contest called "Diablo 3 Image Contest" in "en"
            And I am on the "Demo" site
        When I go to "/contests/image"
            And I click "Diablo 3 Image Contest"
        Then I should be on "/contest/diablo-3-image-contest"
            And I should see "Diablo 3 Image Contest"
            And I should not see "I have read and agreed to the Contest Rules"

    Scenario: Expired contests should not show up on contest index page
        Given there is an expired contest called "Diablo 3 Image Contest" in "en"
            And I am on the "Demo" site
        When I go to "/contests/image"
        Then I should not see "Diablo 3 Image Contest"
