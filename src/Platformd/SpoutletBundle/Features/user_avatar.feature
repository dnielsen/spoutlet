Feature: Users can manage their own avatars
#    Scenario: User can upload own avatar for approval
#        Given that I am logged in
#        And I am on /account/settings
#        When I click "update"
#        Then I should see the "Choose Avatar" modal (see http://grab.by/j4NA)
#        When I click "browse" in one of these formats (JPG, PNG, or GIF)
#        And I click "upload"
#        Then the new image I uploaded will appear in the avatar box
#        And I should see text "Awaiting Approval" on top of the image
#        And I should be on /account/settings
#        And my photo will not be displayed on the member profile page for others to see until it's been approved by an admin
#
#    Scenario: Show message if user uploads an image that is not in the supported format.
#        Given that I am logged in
#        And I am on /account/settings
#        When I click "update"
#        Then I should see the "Choose Avatar" modal (see http://grab.by/j4NA)
#        When I click "browse" and attach a photo that is NOT in one of these formats (JPG, PNG, or GIF)
#        And I click "upload"
#        Then I should see message "The image must be in the JPG, PNG, or GIF format."
#        And the modal will remain open so I can upload another photo again.
#
#    Scenario: User can select previously uploaded avatars to use.
#        Given that I am logged in
#        And I am viewing the Choose Avatar modal http://grab.by/j4PE
#        When I click "choose"
#        Then the modal will close
#        And I should be on /account/settings
#        And the avatar I selected will be displayed in the box, however if I choose an image that is awaiting on approval, then the avatar will show that and
