Feature: Home Page Banner Administration
    In order to control which banners show up on each site's home page
    As an organizer
    I need to be able to add, edit, and publish banners

    Scenario: Add a new banner
        Given I am authenticated as an organizer
            And I am on "/admin"
        When I click to add new "Homepage Banner"
            And I fill in the following:
                | Url       | http://www.alienwarearena.com |
            And I check the "Demo" option for "Sites"
            And I attach the file "src/Platformd/SpoutletBundle/Features/Context/120x60.gif" to "Thumb file"
            And I attach the file "src/Platformd/SpoutletBundle/Features/Context/634x183.gif" to "Banner file"
            And I press "Save"
        Then I should see "success" in the flash message
