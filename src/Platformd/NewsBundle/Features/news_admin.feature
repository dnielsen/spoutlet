Feature: News Administration
    In order to control which news items show up on each site
    As an organizer
    I need to be able to add, edit, and publish news stories

    Scenario: Add a news story
        Given I am authenticated as an organizer
            And I am on "/admin"
        When I click to add new "News"
            And I fill in the following:
                | Title     | My new event  |
                | Site      | en            |
                | Post Date | 2012-05-18    |
                | Blurb     | My cool blurb |
                | Body      | Lorem ipsum   |
            And I press "Save news"
        Then I should see "news item has been created"
