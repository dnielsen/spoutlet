Feature: Games Page
    In order to see lots of enticing information about a new game
    As a web user
    I need to be able to view the trailer, news, photos and other information about a game

    # todo
    Scenario: See only games that are approved for my age

    Scenario: Only show published games
    Scenario: See games organized by their category
        Given I have the following games pages:
            | name | category | status      |
            | Foo  | rpg      | published   |
            | Bar  | rpg      | published   |
            | Baz  | action   | published   |
            | Boo  | mmo      | unpublished |
            And I have verified my age
        When I go to "/games"
        Then I should see 2 games under the "RPG" category
            And I should see 1 game under the "Action" category
            But I shouldn't see any games under the "MMO" category

    Scenario: See all "events" (news, giveaways, etc) related to the game

    Scenario: Archived games are shown on the sidebar
        Given I have the following games pages:
            | name | status     |
            | Foo  | published  |
            | Bar  | archived   |
            And I have verified my age
        When I go to "/games"
        Then I should see 1 game in the archived list

    Scenario: The most recently created games page's trailer is automatically played

    Scenario: Only show games for the locale I'm in
        Given I have the following games pages:
            | name | category | status      | sites |
            | Foo  | rpg      | published   | en,ja |
            | Bar  | rpg      | published   | en    |
            | Baz  | rpg      | published   | ja    |
            And I have verified my age
        When I go to "/games"
        Then I should see 2 games under the "RPG" category