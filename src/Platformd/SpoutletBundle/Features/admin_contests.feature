Feature: Contest Admin
    In order to generate interest in the website's media functionality
    As an organizer
    I need to be able to add/edit/delete contests

    Background:
        Given I am authenticated as an organizer
            And I go to "/admin"
            And there is a game called "Diablo 3"

    Scenario: Add a new contest
        When I click to add new "Contests"
            And I select "Diablo 3" from "Game"
            And I fill in the following:
                | Contest name                 | Diablo 3 Image Contest  |
                | URL string - /contest/       | diablo-3-contest        |
                | Submission Starts:           | 2012-06-12 12:00        |
                | Submission Ends:             | 2013-06-21 12:00        |
                | Voting Starts:               | 2012-06-12 12:00        |
                | Voting Ends:                 | 2013-06-21 12:00        |
                | Timezone                     | UTC                     |
                | Rules                        | Lorem ipsum             |
                | Instructions for contestants | Lorem ipsum             |
                | Instructions for voters      | Lorem ipsum             |
            And I check the "Demo" option for "Sites"
            And I select "image" from "Category"
            And I select "published" from "Status"
            And I press "Create"
        Then I should see "The contest was created!"

    Scenario: List existing contests
        Given there is a contest called "Diablo 3 Image Contest" in "en"
            And there is a contest called "Super Demo Contest" in "en"
            And there is a contest called "Contest for NA!" in "en_US"
        When I click on "Contests"
            And I click on "Demo"
        Then I should see 2 data rows in "image-contest-list"
            And I should see "Diablo 3 Image Contest"
            And I should see "Super Demo Contest"
            And I should not see "Contest for NA!"

    Scenario: Edit existing contest
        Given there is a contest called "Diablo 3 Image Contest" in "en"
        When I click on "Contests"
            And I click on "Demo"
            And I click on "Diablo 3 Image Contest"
            And I fill in the following:
                | Contest name    | Diablo 3 Image Contest Updated!    |
            And I press "Save"
        Then I should see "The contest was saved!"

    Scenario: Preview the contest
        Given there is a contest called "Diablo 3 Image Contest" in "en"
        When I click on "Contests"
            And I click on "Demo"
            And I click on the URL for "Diablo 3 Image Contest"
        Then I should be on the contest called "Diablo 3 Image Contest" in "en"
