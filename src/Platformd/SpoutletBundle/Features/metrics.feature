Feature: Metrics
  In order to track the success of different areas of the site
  As an admin
  I need to view statistics about different things

  Background:
    Given I have an account
     And I have the "ROLE_ADMIN, ROLE_STATS_ADMIN" permissions
     And I am authenticated

  Scenario: I can access the giveaways metrics page
    When I go to "/admin/metrics/giveaways"
    Then the "h1" element should contain "Key Giveaways"
     And I should see a "table.table-striped" element

  Scenario: I can access the members metrics page
    When I go to "/admin/metrics/users/country"
     Then the "h1" element should contain "Members by Country"
      And I should see a "table.table-striped" element
