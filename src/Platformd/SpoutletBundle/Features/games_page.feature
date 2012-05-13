Feature: Games Page
    In order to see lots of enticing information about a new game
    As a web user
    I need to be able to view the trailer, news, photos and other information about a game

    Background:
        # shouldn't matter, but doing this to be extra hardcore
        # it's more likely that this would break, due to our site listener
        # that redirects users back to aa.com when accessing pages they should not
        Given I am on the "Europe" site

    # todo
    Scenario: See only games that are approved for my age

    Scenario: Only show published games
    Scenario: See games organized by their category
        Given I have the following games pages:
            | name | category | status      | sites |
            | Foo  | rpg      | published   | en_GB |
            | Bar  | rpg      | published   | en_GB |
            | Baz  | action   | published   | en_GB |
            | Boo  | mmo      | unpublished | en_GB |
            And I have verified my age
        When I go to "/games"
        Then I should see 2 games under the "RPG" category
            And I should see 1 game under the "Action" category
            But I shouldn't see any games under the "MMO" category

    Scenario: See all "events" (news, giveaways, etc) related to the game

    Scenario: Archived games are shown on the sidebar
        Given I have the following games pages:
            | name | status     | sites |
            | Foo  | published  | en_GB |
            | Bar  | archived   | en_GB |
            And I have verified my age
        When I go to "/games"
        Then I should see 1 game in the archived list

    Scenario: The most recently created games page's trailer is automatically played

    Scenario: Only show games for the locale I'm in
        Given I have the following games pages:
            | name | category | status      | sites    |
            | Foo  | rpg      | published   | en_GB,ja |
            | Bar  | rpg      | published   | en_GB    |
            | Baz  | rpg      | published   | ja       |
            And I have verified my age
        When I go to "/games"
        Then I should see 2 games under the "RPG" category

    Scenario: See information about a specific game
        Given there is a game page for "Awesome Game" in "en_GB"
            And I have verified my age
            And I am on "/games"
        When I click "Awesome Game"
            Then the headline should contain "Awesome Game"