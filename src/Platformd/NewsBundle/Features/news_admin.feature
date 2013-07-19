Feature: News Administration
    In order to control which news items show up on each site
    As an organizer
    I need to be able to add, edit, and publish news stories

    Background:
        Given I am authenticated as an organizer
            And there is a game called "Skyrim"

    Scenario: Add a news story
        Given I am on "/admin"
        When I click to add new "News"
            And I fill in the following:
                | Title     | My new event  |
                | Post Date | 2012-05-18    |
                | Blurb     | My cool blurb |
                | Body      | Lorem ipsum   |
            And I attach the file "foo.jpg" to "Upload an image"
            And I check the "Demo" option for "Sites"
            And I select "Skyrim" from "Game"
            And I press "Save news"
        Then I should see "news item has been created"

    Scenario: Edit a news story
        Given there is a news item called "Skryim release"
            And I am on the edit page for the news story
        When I select "Skyrim" from "Game"
            And I fill in "Title" with "Updated title"
            And I press "Save news"
        Then I should see "news item has been modified"
