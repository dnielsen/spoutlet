Feature: Admin
  In order to enter new sweepstakes
  As an admin
  I need to manage my sweepstakes

  Background:
    Given I have an account
     And I have the "ROLE_ADMIN, ROLE_ORGANIZER" permissions
     And I am authenticated

  Scenario: I can create a new sweepstakes
     Given I have no "SweepstakesBundle:Sweepstakes" in the database
    When I go to "/admin"
     And I follow "Sweepstakes"
     And I follow "Create"
     And I fill in "Name" with "My sweepstakes"
     And I select "Demo" from "Site"
     And I fill in "Starts at" with "06/05/2012"
     And I fill in "Ends at" with "06/15/2012"
     And I select "us" from "Eligible Countries"
     And I fill in "Official Rules" with "the rules!"
     And I fill in "Content" with "the release!"
     And I press "Save"
    Then I should see "Sweepstakes Saved"


  Scenario: I can export sweepstakes results
    Given there is a sweepstakes
     And some people are entered into the sweepstakes
    When I go to "/admin/sweepstakes/metrics"
     And I follow "view"
     And I follow "Download CSV"
    Then the response status code should be 200
