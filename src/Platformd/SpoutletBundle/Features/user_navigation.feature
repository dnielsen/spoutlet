@mink:goutte
Feature: User Navigation
  In order to find my way around the site
  As an user
  I should only see user navigation links to features that are enabled for the site I'm on

  Background:
    Given I am authenticated as a user
    # And my CEVO User ID is 55 # This is just here for reference, don't uncomment, the system is setup with a default user who actually has a CEVO User ID of 55

  Scenario: The "Japan" site's user navigation menu has the correct items
    Given I am on the "Japan" site
    When I go to "/app_test.php/account"
    Then the user navigation menu should be:
      | Link                     | Target                                                | Destination                                                                | CompareWithRedirects |
      | アカウントホーム               | http://alienwarearena.com/account/                    |                                                                           |                       |
      | パスワード変更               | http://alienwarearena.com/account/password/            |                                                                           |                      |
      | プロファイル                  | http://www.alienwarearena.com/japan/member/55/         |                                                                           |                      |
      | 参加済みキャンペーン           | /app_test.php/account/profile/giveaways                | http://japan.alienwarearena.local/app_test.php/account/profile/giveaways |                       |
      | トーナメント >> ゲームID        | http://www.alienwarearena.com/japan/account/ids/       |                                                                           |                      |
      | トーナメント >> トーナメント・イベント | http://www.alienwarearena.com/japan/account/events/    |                                                                           |                      |
      | ログアウト                    | /app_test.php/logout                                  | http://demo.alienwarearena.local/app_test.php/cevo/api/stub/japan/cmd/account/logout?return=http%3A%2F%2Fjapan.alienwarearena.local%2Fapp_test.php%2F | |

  Scenario: The "Latin America" site's user navigation menu has the correct items
    Given I am on the "Latin America" site
    When I go to "/app_test.php/account"
    Then the user navigation menu should be:
      | Link                          | Target                                                          | Destination |
      | Account Home                  | http://alienwarearena.com/account/                              |             |
      | Change Pass                   | http://alienwarearena.com/account/password/                     |             |
      | Profile                       | http://www.alienwarearena.com/latam/member/55/                  |             |
      | Messages                      | http://alienwarearena.com/account/inbox/                        |             |
      | Giveaways                     | http://www.alienwarearena.com/latam/account/my-giveaway-keys/   |             |
      | Tournaments >> Match Client   | http://alienwarearena.com/account/client/                       |             |
      | Tournaments >> Match Lobby    | http://alienwarearena.com/external/match-lobby/                 |             |
      | Media >> Gallery              | /app_test.php/account/profile/photos                            | http://latam.alienwarearena.local/app_test.php/account/profile/photos |
      | Sign Out                      | /app_test.php/logout                                            | http://demo.alienwarearena.local/app_test.php/cevo/api/stub/cmd/account/logout?return=http%3A%2F%2Flatam.alienwarearena.local%2Fapp_test.php%2F |

  Scenario: The "North America" site's user navigation menu has the correct items
    Given I am on the "North America" site
    When I go to "/app_test.php/account"
    Then the user navigation menu should be:
      | Link                          | Target                                                    | Destination                                                        |
      | Account Home                  | http://alienwarearena.com/account/                        |                                                                    |
      | Change Pass                   | http://alienwarearena.com/account/password/               |                                                                    |
      | Profile                       | http://www.alienwarearena.com/member/55/                  |                                                                    |
      | Messages                      | http://alienwarearena.com/account/inbox/                  |                                                                    |
      | Events                        | /app_test.php/account/profile/events                      | http://na.alienwarearena.local/app_test.php/account/profile/events |
      | Giveaways                     | http://www.alienwarearena.com/account/my-giveaway-keys/   |                                                                    |
      | Deals                         | /app_test.php/account/profile/deals                       | http://na.alienwarearena.local/app_test.php/account/profile/deals  |
      | Groups                        | /app_test.php/account/profile/groups                      | http://na.alienwarearena.local/app_test.php/account/profile/groups |
      | Tournaments >> Match Client   | http://alienwarearena.com/account/client/                 |                                                                    |
      | Tournaments >> Match Lobby    | http://alienwarearena.com/external/match-lobby/           |                                                                    |
      | Tournaments >> Competitions   | http://www.alienwarearena.com/account/events/             |                                                                    |
      | Media >> Gallery              | /app_test.php/account/profile/photos                      | http://na.alienwarearena.local/app_test.php/account/profile/photos |
      | Sign Out                      | /app_test.php/logout                                      | http://demo.alienwarearena.local/app_test.php/cevo/api/stub/cmd/account/logout?return=http%3A%2F%2Fna.alienwarearena.local%2Fapp_test.php%2F |

  Scenario: The "Europe" site's user navigation menu has the correct items
    Given I am on the "Europe" site
    When I go to "/app_test.php/account"
    Then the user navigation menu should be:
      | Link                          | Target                                                          | Destination                                                        |
      | Account Home                  | http://alienwarearena.com/account/                              |                                                                    |
      | Change Pass                   | http://alienwarearena.com/account/password/                     |                                                                    |
      | Profile                       | http://www.alienwarearena.com/europe/member/55/                 |                                                                    |
      | Messages                      | http://alienwarearena.com/account/inbox/                        |                                                                    |
      | Events                        | /app_test.php/account/profile/events                            | http://eu.alienwarearena.local/app_test.php/account/profile/events |
      | Giveaways                     | http://www.alienwarearena.com/europe/account/my-giveaway-keys/  |                                                                    |
      | Deals                         | /app_test.php/account/profile/deals                             | http://eu.alienwarearena.local/app_test.php/account/profile/deals  |
      | Groups                        | /app_test.php/account/profile/groups                            | http://eu.alienwarearena.local/app_test.php/account/profile/groups |
      | Tournaments >> Match Client   | http://alienwarearena.com/account/client/                       |                                                                    |
      | Tournaments >> Match Lobby    | http://alienwarearena.com/external/match-lobby/                 |                                                                    |
      | Media >> Gallery              | /app_test.php/account/profile/photos                            | http://eu.alienwarearena.local/app_test.php/account/profile/photos |
      | Sign Out                      | /app_test.php/logout                                            | http://demo.alienwarearena.local/app_test.php/cevo/api/stub/cmd/account/logout?return=http%3A%2F%2Feu.alienwarearena.local%2Fapp_test.php%2F |

  Scenario: The "India" site's user navigation menu has the correct items
    Given I am on the "India" site
    When I go to "/app_test.php/account"
    Then the user navigation menu should be:
      | Link                          | Target                                                            | Destination |
      | Account Home                  | http://alienwarearena.com/account/                                |             |
      | Change Pass                   | http://alienwarearena.com/account/password/                       |             |
      | Profile                       | http://www.alienwarearena.com/in/member/55/                       |             |
      | Messages                      | http://alienwarearena.com/account/inbox/                          |             |
      | Giveaways                     | http://www.alienwarearena.com/in/account/my-giveaway-keys/        |             |
      | Tournaments >> Match Client   | http://alienwarearena.com/account/client/                         |             |
      | Tournaments >> Match Lobby    | http://alienwarearena.com/external/match-lobby/                   |             |
      | Sign Out                      | /app_test.php/logout                                              | http://demo.alienwarearena.local/app_test.php/cevo/api/stub/cmd/account/logout?return=http%3A%2F%2Fin.alienwarearena.local%2Fapp_test.php%2F |

  Scenario: The "Singapore" site's user navigation menu has the correct items
    Given I am on the "Singapore" site
    When I go to "/app_test.php/account"
    Then the user navigation menu should be:
      | Link                          | Target                                                        | Destination |
      | Account Home                  | http://alienwarearena.com/account/                            |             |
      | Change Pass                   | http://alienwarearena.com/account/password/                   |             |
      | Profile                       | http://www.alienwarearena.com/sg/member/55/                   |             |
      | Messages                      | http://alienwarearena.com/account/inbox/                      |             |
      | Giveaways >> Giveaway Keys    | http://www.alienwarearena.com/sg/account/my-giveaway-keys/    |             |
      | Giveaways >> System Tag Keys  | /app_test.php/account/profile/giveaways                       | http://mysg.alienwarearena.local/app_test.php/account/profile/giveaways |
      | Tournaments >> Game IDs       | http://www.alienwarearena.com/account/ids/                    |             |
      | Tournaments >> Match Client   | http://alienwarearena.com/account/client/                     |             |
      | Tournaments >> Match Lobby    | http://alienwarearena.com/external/match-lobby/               |             |
      | Tournaments >> Competitions   | http://www.alienwarearena.com/sg/account/events/              |             |
      | Sign Out                      | /app_test.php/logout                                          | http://demo.alienwarearena.local/app_test.php/cevo/api/stub/cmd/account/logout?return=http%3A%2F%2Fmysg.alienwarearena.local%2Fapp_test.php%2F |

  Scenario: The "Australia / New Zealand" site's user navigation menu has the correct items
    Given I am on the "Australia / New Zealand" site
    When I go to "/app_test.php/account"
    Then the user navigation menu should be:
      | Link                          | Target                                                        | Destination |
      | Account Home                  | http://alienwarearena.com/account/                            |             |
      | Change Pass                   | http://alienwarearena.com/account/password/                   |             |
      | Profile                       | http://www.alienwarearena.com/anz/member/55/                  |             |
      | Messages                      | http://alienwarearena.com/account/inbox/                      |             |
      | Giveaways                     | http://www.alienwarearena.com/anz/account/my-giveaway-keys/   |             |
      | Tournaments >> Match Client   | http://alienwarearena.com/account/client/                     |             |
      | Tournaments >> Match Lobby    | http://alienwarearena.com/external/match-lobby/               |             |
      | Sign Out                      | /app_test.php/logout                                          | http://demo.alienwarearena.local/app_test.php/cevo/api/stub/cmd/account/logout?return=http%3A%2F%2Fanz.alienwarearena.local%2Fapp_test.php%2F |
