Feature: Authentication
    In order to participate in the site community
    I need to be able to log in

    Scenario: Admin authentication
        Given I have the "ROLE_SUPER_ADMIN" role
        When I go to "/logout"
            And I go to "/login"
            And I fill in "login-username" with "user"
            And I fill in "login-password" with "user"
            And I press "_submit"
            And I go to "/admin"
        Then I should see "Welcome to the admin area"
