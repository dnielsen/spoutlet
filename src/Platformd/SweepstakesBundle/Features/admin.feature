Feature: Admin
  In order to enter new sweepstakes
  As an admin
  I need to manage my sweepstakes

  Scenario: I can create a new sweepstakes
    Given I am authenticated as "admin"
     And I have no "Sweepstake" in the database
    When I go to "/admin"
     And I follow "Giveaways"
     And I follow "New"
     And I fill in "Name" with "My sweepstakes"
     And I choose "us" from "Disallowed Countries"
     And I fill in "Rules" with "the rules!"
     And I fill in "Liabiliy Release" with "the release!"
     And I fill in "Start date" with "2012-06-05 10:15:25"
     And I fill in "End date" with "2012-06-15 10:15:25"
     And I press "Save"
    Then I should see "Your sweepstakes was saved"
     And I should see a "table.zebra-striped tbody tr" element

