Feature: Youtube Video Submit
    In order to generate more video content on the site
    As an user
    I need to be able to add a YouTube video

#    Background:
#        Given I am authenticated as a user
#            And I have the following galleries:
#                |owner_id |slug                 |name                  |sites_position |
#                |3        |original-productions | Original Productions | 1             |
#
#    Scenario: Submit a new video
#        Given I am on "/videos/submit"
#            And I fill in "YouTube Link" with "http://www.youtube.com/watch?v=TOypSnKFHrE"
#            And I fill in "Description" with "Music video by The Strokes performing Last Nite. (C) 2001 BMG"
#            And I fill in "youtube_youtubeId" with "TOypSnKFHrE"
#            And I fill in "youtube_duration" with "198"
#            And I check the "Original Productions" option for "Category"
#            And I press "Save"
#            Then I should be on "/videos/view/the-strokes-last-nite"
#
