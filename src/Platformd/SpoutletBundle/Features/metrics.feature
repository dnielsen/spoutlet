Feature: Metrics
  In order to track the success of different areas of the site
  As an admin
  I need to view statistics about different things

  Scenario: I can access the giveaways metrics page
    Given I am authenticated as "admin"
    When I go to "/admin/giveaways/metrics"
     Then the "h1" element should contain "Key Giveaways"
      And I should see a "table.zebra-striped" element

  Scenario: I can access the members metrics page
    Given I am authenticated as "admin"
    When I go to "/admin/metrics/users/country"
     Then the "h1" element should contain "Members by Country"
      And I should see a "table.zebra-striped" element
