Feature: Metrics
  In order to track the success of our giveaways
  As an admin
  I need to view statistics about giveaways

  Scenario: I can access the metrics page
    Given I am authenticated as "admin"
    When I go to "/admin/giveaways/metrics"
     Then the "h1" element should contain "Key Giveaways"
      And I should see a "table.zebra-striped" element
