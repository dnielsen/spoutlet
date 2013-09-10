Feature: Admin background ads
    In order to advertise people
    As an admin
    I need to be able to add/edit/delete background ads

    Background:
        Given I am authenticated as an admin

# Commented out until we implement javascript capable testing
#    Scenario: add a new background ad
#        Given I am on "/admin/background-ads/new"
#        And I fill in the following:
#                | Title                     | alienware deal            |
#                | Start date                | 2012-12-21 12:00          |
#                | End date                  | 2012-12-22 12:00          |
#                | Timezone                  | Europe/Paris              |
#        And I attach a background ad image
#        And I check the "Europe" option for "Sites"
#        And I check the "North America" option for "Sites"
#        #And I fill in "admin_background_ad_adSites_0_url" with "http://eu.alienwarearena.com"
#        #And I fill in "admin_background_ad_adSites_1_url" with "http://na.alienwarearena.com"
#        #And I select countries in Europe and North America that should see the banner
#        When I press "Create"
#        Then I should see "Background ad successully created"


#    Scenario: Do not allow admin to schedule more than one background ad to appear at the same time for the same region.
#        Given there is an already existing background ad at date "2012/12/21" - "2012/12/22" for site "Europe"
#        And I am on "/admin/background-ads/new"
#        And I fill in the following:
#                | Title                     | alienware deal            |
#                | Start date                | 2012-12-21 12:00          |
#                | End date                  | 2012-12-22 12:00          |
#                | Timezone                  | Europe/Paris              |
#        And I attach a background ad image
#        And I check the "Europe" option for "Sites"
#        And I check the "North America" option for "Sites"
#        When I press "Create"
#        Then I should see "Error! This schedule conflicts with another banner that is scheduled at the same time. Please uncheck the conflicting site."

