Feature: Groups Frontend
    In order to participate in the site community
    As a user
    I need to be able to view/join groups

    Background:
        Given I am authenticated as a user
            And I am on the "Demo" site
            And I have the following users:
                | username      | email                 |
                | Notme         | someone@example.com   |
                | StillNotMe    | definitelynotme@a.com |
            And I have the following groups:
                | name            | slug           | site  | category | description                  | public | featured | owner |
                | Group 1         | group-1        | en    | topic    | this is group 1              | yes    | no       |       |
                | Other Group     | other-group    | en    | topic    | this is some other group     | no     | no       |       |
                | Someone's Group | someones-group | en    | topic    | this is someone else's group | yes    | no       | Notme |
                | Private Group   | private-group  | en    | topic    | this is a private group      | no     | no       | Notme |
            And the group "Other Group" has an outstanding application from "Notme"

    Scenario: Create a group
        Given I am on "/groups/"
        When I click on "Create Group"
            And I fill in the following:
                | Group Name                | New test group     |
                | Group Description         | Some information   |
            And I select "Topic" from "Group Category"
            And I select the "Public Group" radio button
            And I press "Create"
        Then I should see "The group was created!"
            And I should see "New test group" on "/groups/"

    Scenario: View group page
        Given I am on "/groups/"
        When I click "Group 1"
        Then I should be on "/group-1/"
            And I should see "Group 1"

    Scenario: Edit a group
        Given I am on "/groups/"
        When I click on "Group 1"
            And I click on "Group Settings"
            And I fill in the following:
                | Group Name                | New test group updated     |
                | Group Description         | Some updated information   |
            And I press "Save"
        Then I should see "The group was saved!"
            And I should see "New test group updated" on "/groups/"

    Scenario: Apply to a public group
        Given I am on "/groups/"
        When I click on "Someone's Group"
            And I press "join-group-button"
        Then I should see "You have successfully joined this group!"

    Scenario: Apply to a private group
        Given I am on "/groups/"
        When I click on "Private Group"
            And I click on "join-group-button"
            And I fill in "Reason" with "Because I want to join!"
            And I press "Apply"
        Then I should see "Waiting on Approval"
            And I should see "You will receive an email if you are admitted into this group."

    Scenario: View group applicants
        Given I am on "/groups/"
        When I click on "Other Group"
            And I click on "Member Approvals"
        Then I should see 1 data rows

    Scenario: Approve successful applicant
        Given I am on "/groups/"
        When I click on "Other Group"
            And I click on "Member Approvals"
            And I accept an application to "Other Group"
        Then I should see "You have successfully accepted"

    Scenario: Disapprove unsuccessful applicant
        Given I am on "/groups/"
        When I click on "Other Group"
            And I click on "Member Approvals"
            And I reject an application to "Other Group"
        Then I should see "You have successfully rejected"

    Scenario: Add group news
        Given I am on "/group-1/"
            And I add "news" for group "Group 1"
        When I fill in the following:
            | Title   | News Title       |
            | Article | Article content |
            And I press "Post News"
        Then I should see "New article posted successfully."
            And I should see "News Title" on the "news" page of "Group 1"

    # Commented out until Javascript testing is implemented
    #Scenario: Upload group image
    #    Given I am on "/galleries/submit-image"
    #    When I attach the file "src/Platformd/SpoutletBundle/Features/Context/120x60.gif" to "galleryImages"
    #        And I click "Upload"
    #    Then I should see "Your images were uploaded successfully."
    #
    #Scenario: Publish group image
    #    Given I am on "/galleries/submit-image"
    #    When I attach the file "src/Platformd/SpoutletBundle/Features/Context/120x60.gif" to "galleryImages"
    #        And I click "Upload"
    #        And fill in "Image Description" for "Description"
    #        And select "Group 1" from "Groups"
    #        And I click "Publish"
    #    Then I should see "1 of 1 images are published."
    #        And I should see "120x60.gif" on "/groups/1/images/"
    #
    #Scenario: Add group video
    #    Given I am on "/groups/group-1"
    #    And I add "video" for group "Group 1"
    #    When I fill in the following:
    #        | Title           | Test Video       |
    #        | YouTube Link    | Y0h6WIjZluM      |
    #        | Description     | Description      |
    #        And I check the "Group 1" option for "Groups"
    #        And I press "Save"
    #    Then I should see "Your video is uploaded."
    #        And I should see "Test Video" on the "videos" page of "Group 1"

    Scenario: Add group discussion topic
        Given I am on "/group-1"
        And I add "discussion" for group "Group 1"
        When I fill in the following:
            | Discussion Name   | Test Discussion           |
            | Content           | Here is a test discussion |
            And I press "Post Discussion"
        Then I should see "New discussion posted successfully."
            And I should see "Test Discussion" on the "discussions" page of "Group 1"

    Scenario: Add reply to group discussion
        Given I am on "/group-1"
        And I am authenticated as a user
        And I add "discussion" for group "Group 1"
        When I fill in the following:
            | Discussion Name   | Test Discussion           |
            | Content           | Here is a test discussion |
            And I press "Post Discussion"
            And I go to the "discussions" page of "Group 1"
            And I click on "Test Discussion"
            And I fill in "Discussion Reply" for "form_content"
            And I press "Post to Discussion"
        Then I should see "Discussion Reply"

    Scenario: Member count
        Given "Group 1" has the following members:
            | username   |
            | NotMe      |
            | StillNotMe |
        When I go to "/group-1"
        Then the "Members" count should be 3

    Scenario: Comment count
        Given group "Group 1" has the following comments:
            | username   | comment        |
            | NotMe      | first comment  |
            | StillNotMe | second comment |
        When I go to "/group-1"
        Then the "Comments" count should be 2
