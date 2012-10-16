Feature: User Navigation
  In order to find my way around the site
  As an user
  I should only see user navigation links to features that are enabled for the site I'm on

  Background:
    Given I am authenticated as a user
    # And my CEVO User ID is 55 # This is just here for reference, don't uncomment - the system is setup with a default user who actually has a CEVO User ID of 55

  Scenario: The "Japan" site's user navigation menu has the correct items
    Given I am on the "Japan" site
    When I go to "/account"
    Then the user navigation menu should be:
      | Link                | Target                                                |
      | アカウントホーム          | http://alienwarearena.com/account/                    |
      | パスワード変更          | http://alienwarearena.com/account/password/            |
      | プロファイル             | /account/profile                                       |
      | 参加済みイベント         | http://www.alienwarearena.com/japan/account/events/   |
      | 参加済みキャンペーン      | /account/profile/giveaways                            |
      | トーナメント >> ゲームID   | http://www.alienwarearena.com/japan/account/ids/      |
      | ログアウト               | /logout                                               |

  Scenario: The "China" site's user navigation menu has the correct items
    Given I am on the "China" site
    When I go to "/account"
    Then the user navigation menu should be:
      | Link             | Target                                                 |
      | 账户首页          | http://alienwarearena.com/account/                     |
      | 修改密码          | http://alienwarearena.com/account/password/            |
      | 个人形象          | /account/profile                                       |
      | 我的活动          | http://www.alienwarearena.com/china/account/events/    |
      | 获取赠品          | /account/profile/giveaways                             |
      | 比赛 >> 游戏IDs   | http://www.alienwarearena.com/china/account/ids/             |
      | 退出              | /logout                                                |

  Scenario: The "Latin America" site's user navigation menu has the correct items
    Given I am on the "Latin America" site
    When I go to "/account"
    Then the user navigation menu should be:
      | Link                          | Target                                                          |
      | Account Home                  | http://alienwarearena.com/account/                              |
      | Change Password               | http://alienwarearena.com/account/password/                     |
      | Profile                       | http://www.alienwarearena.com/latam/member/55/                  |
      | Messages                      | http://alienwarearena.com/account/inbox/                        |
      | My Giveaways                  | http://www.alienwarearena.com/latam/account/my-giveaway-keys/   |
      | Tournaments >> Match Client   | http://alienwarearena.com/account/client/                       |
      | Tournaments >> Match Lobby    | http://alienwarearena.com/external/match-lobby/                 |
      | Cerrar sesión                 | /logout                                                         |

  Scenario: The "North America" site's user navigation menu has the correct items
    Given I am on the "North America" site
    When I go to "/account"
    Then the user navigation menu should be:
      | Link                          | Target                                                    |
      | Account Home                  | http://alienwarearena.com/account/                        |
      | Change Password               | http://alienwarearena.com/account/password/               |
      | Profile                       | http://www.alienwarearena.com/member/55/                  |
      | Messages                      | http://alienwarearena.com/account/inbox/                  |
      | My Events                     | http://www.alienwarearena.com/account/events/             |
      | My Giveaways                  | http://www.alienwarearena.com/account/my-giveaway-keys/   |
      | My Deals                      | /account/profile/deals                                    |
      | Tournaments >> Match Client   | http://alienwarearena.com/account/client/                 |
      | Tournaments >> Match Lobby    | http://alienwarearena.com/external/match-lobby/           |
      | Sign out                      | /logout                                                   |

  Scenario: The "Europe" site's user navigation menu has the correct items
    Given I am on the "Europe" site
    When I go to "/account"
    Then the user navigation menu should be:
      | Link                          | Target                                                          |
      | Account Home                  | http://alienwarearena.com/account/                              |
      | Change Password               | http://alienwarearena.com/account/password/                     |
      | Profile                       | http://www.alienwarearena.com/europe/member/55/                 |
      | Messages                      | http://alienwarearena.com/account/inbox/                        |
      | My Giveaways                  | http://www.alienwarearena.com/europe/account/my-giveaway-keys/  |
      | My Deals                      | /account/profile/deals                                          |
      | Tournaments >> Match Client   | http://alienwarearena.com/account/client/                       |
      | Tournaments >> Match Lobby    | http://alienwarearena.com/external/match-lobby/                 |
      | Sign out                      | /logout                                                         |

  Scenario: The "India" site's user navigation menu has the correct items
    Given I am on the "India" site
    When I go to "/account"
    Then the user navigation menu should be:
      | Link                          | Target                                                            |
      | Account Home                  | http://alienwarearena.com/account/                                |
      | Change Password               | http://alienwarearena.com/account/password/                       |
      | Profile                       | http://www.alienwarearena.com/in/member/55/                       |
      | Messages                      | http://alienwarearena.com/account/inbox/                          |
      | My Giveaways                  | http://www.alienwarearena.com/in/account/my-giveaway-keys/        |
      | Tournaments >> Match Client   | http://alienwarearena.com/account/client/                         |
      | Tournaments >> Match Lobby    | http://alienwarearena.com/external/match-lobby/                   |
      | Sign out                      | /logout                                                           |

  Scenario: The "Singapore" site's user navigation menu has the correct items
    Given I am on the "Singapore" site
    When I go to "/account"
    Then the user navigation menu should be:
      | Link                          | Target                                                        |
      | Account Home                  | http://alienwarearena.com/account/                            |
      | Change Password               | http://alienwarearena.com/account/password/                   |
      | Profile                       | http://www.alienwarearena.com/sg/member/55/                   |
      | Messages                      | http://alienwarearena.com/account/inbox/                      |
      | My Giveaways                  | http://www.alienwarearena.com/sg/account/my-giveaway-keys/    |
      | Tournaments >> Match Client   | http://alienwarearena.com/account/client/                     |
      | Tournaments >> Match Lobby    | http://alienwarearena.com/external/match-lobby/               |
      | Sign out                      | /logout                                                       |

  Scenario: The "Australia / New Zealand" site's user navigation menu has the correct items
    Given I am on the "Australia / New Zealand" site
    When I go to "/account"
    Then the user navigation menu should be:
      | Link                          | Target                                                        |
      | Account Home                  | http://alienwarearena.com/account/                            |
      | Change Password               | http://alienwarearena.com/account/password/                   |
      | Profile                       | http://www.alienwarearena.com/anz/member/55/                  |
      | Messages                      | http://alienwarearena.com/account/inbox/                      |
      | My Giveaways                  | http://www.alienwarearena.com/anz/account/my-giveaway-keys/   |
      | Tournaments >> Match Client   | http://alienwarearena.com/account/client/                     |
      | Tournaments >> Match Lobby    | http://alienwarearena.com/external/match-lobby/               |
      | Sign out                      | /logout                                                       |
