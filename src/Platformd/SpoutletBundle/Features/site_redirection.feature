Feature: Site Redirection
    In order to have a fluid user experience
    As a web user
    I need to be taken back to the main site automatically when I visit links hosted in the main site
    @mink:goutte
    Scenario: Visit the contact page from a site that we host
        Given I am on the "Demo" site
            And I am on "/"
        When I click "Contact"
            Then the headline should contain "Contact"
            And I should still be on the "Demo" site
    @mink:goutte
    Scenario: Visit the contact page from a site that we do not host
        Given I am on the "Europe" site
            And I am on "/app_test.php/games/"
        When I click "Contact"
        Then I should be on "http://www.alienwarearena.com/pages/contact"
