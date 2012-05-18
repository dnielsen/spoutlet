Feature: Age Verification
    In order to prevent underage users from accessing certain content
    # this is weak, but hard. Technically, the "role" here seems like the site administrators
    # because we're the ones getting the "value" from this feature, not the web user
    As a web user
    I need to be able to prove my age

    Scenario: Access a page that requires an age verification redirects me
        # use the games page as an example
        When I go to "/games"
        Then I should see "Confirm Your Date of Birth"

    # this one is hard to actually "visually" test as a user
    # which, again, is because our "role" is actually wrong with this test
    Scenario: Fill out the age verification and it's recorded
        When I go to "/age/verify"
            # this is a slight mess - I'm cheating by referencing the field's name
            # also, I should say "June", not "6"
            And I select "1984" from "birthday[year]"
            And I select "6" from "birthday[month]"
            And I select "5" from "birthday[day]"
            And I press "Confirm"
            And I go to "/games"
        Then the headline should contain "GAMES AND TRAILERS"

    Scenario: I'm redirected back to my original page after verification
        When I go to "/games"
            And I select "1984" from "birthday[year]"
            And I select "6" from "birthday[month]"
            And I select "5" from "birthday[day]"
            And I press "Confirm"
        Then the headline should contain "GAMES AND TRAILERS"

    Scenario: I should see the "access denied" screen if I'm verified, but not old enough
        When I go to "/games"
            And I select "2010" from "birthday[year]"
            And I select "6" from "birthday[month]"
            And I select "5" from "birthday[day]"
            And I press "Confirm"
        Then the headline should contain "Content Intended for Mature Audiences"
