Feature: Sweepstake Admin
    In order to control which sweepstakes show up on each site
    As an admin
    I need to be able to add and edit sweepstakes

    Background:
        Given I am authenticated as an admin
            And there is a game called "Skyrim"

    Scenario: I can create a new sweepstakes
        Given I am on "/admin"
        When I click to add new "Sweepstakes"
            And I fill in the following:
                | Name                  | My sweepstakes        |
                | External URL          | http://www.google.com |
                | Entry begins          | 06/05/2012            |
                | Entry ends            | 06/15/2012            |
                | Official Rules        | the rules!            |
                | Content               | the release!          |
            And I check the "Demo" option for "Sites"
            And I press "Save"
        Then I should see "Sweepstakes Saved"

    Scenario: I can export sweepstakes results
        Given there is a sweepstakes
            And some people are entered into the sweepstakes
        When I go to "/admin/sweepstakes/metrics"
            And I click "export-all-1"
        Then the response status code should be 200
