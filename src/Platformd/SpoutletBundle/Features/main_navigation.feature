Feature: Main Navigation
  In order to find my way around the site
  As an user
  I should only see main navigation links to features that are enabled for the site I'm on

  Scenario: The "Japan" site's main navigation menu has the correct items
    Given I am on the "Japan" site
    When I go to "/games"
    Then the main navigation menu should be:
      | Link        | Target                |
      | ホーム        | /                     |
      | Alienware   | http://alienware.jp/  |
      | イベント       | /events/              |
      | ニュース       | /news                 |
      | Microsoft   | /microsoft            |

  Scenario: The "China" site's main navigation menu has the correct items
    Given I am on the "China" site
    When I go to "/games"
    Then the main navigation menu should be:
      | Link       | Target                    |
      | 首页        | /                         |
      | Alienware   | http://alienware.com.cn/  |
      | 活动        | /events/                  |
      | 新闻        | /news                     |
      | 壁纸下载    | /wallpapers               |
      | 微软        | /microsoft                |

  Scenario: The "Latin America" site's main navigation menu has the correct items
    Given I am on the "Latin America" site
    When I go to "/games"
    Then the main navigation menu should be:
      | Link                        | Target                                      |
      | Inicio                      | /                                           |
      | Alienware                   | http://www.alienware.com/mx/                |
      | Eventos                     | http://www.alienwarearena.com/latam/event/  |
      | Novedades >> Articles       | http://www.alienwarearena.com/articles/     |
      | Novedades >> Announcements  | http://www.alienwarearena.com/news/         |
      | Medios >> Vídeos            | http://video.alienwarearena.com/            |
      | Medios >> Explorar          | /pages/wallpapers                           |
      | Foros                       | /forums                                     |
      | ARP                         | /arp                                        |
      | Microsoft                   | /microsoft                                  |

  Scenario: The "North America" site's main navigation menu has the correct items
    Given I am on the "North America" site
    When I go to "/games"
    Then the main navigation menu should be:
      | Link                        | Target                                          |
      | Home                        | /                                               |
      | Alienware                   | http://www.alienware.com/                       |
      | Games >> Battlefield 3      | http://alienwarearena.com/game/bf3/             |
      | Games >> League of Legends  | http://alienwarearena.com/game/lol/             |
      | Games >> Starcraft II       | http://alienwarearena.com/game/sc2/             |
      | Games >> Rift               | http://alienwarearena.com/game/rift/            |
      | Games >> More Games         | /games/                                         |
      | Events                      | http://www.alienwarearena.com/event/            |
      | News >> Articles            | http://www.alienwarearena.com/articles/         |
      | News >> Announcements       | http://www.alienwarearena.com/news/             |
      | SWEEPS                      | /sweepstakes                                    |
      | Media >> Videos             | http://video.alienwarearena.com/                |
      | Media >> Wallpapers         | /pages/wallpapers                               |
      | NewDeals                    | /deal/                                          |
      | NewGroups                   | /groups/                                        |
      | Forums                      | /forums                                         |
      | ARP                         | /arp                                            |
      | Microsoft                   | /microsoft                                      |

  Scenario: The "Europe" site's main navigatdfion menu has the correct items
    Given I am on the "Europe" site
    When I go to "/games"
    Then the main navigation menu should be:
      | Link                        | Target                                                                                            |
      | Home                        | /                                                                                                 |
      | Alienware                   | http://www1.euro.dell.com/content/topics/segtopic.aspx/alienware?c=uk&cs=ukdhs1&l=en&s=dhs&~ck=mn |
      | Games >> Battlefield 3      | http://alienwarearena.com/game/bf3/                                                               |
      | Games >> League of Legends  | http://alienwarearena.com/game/lol/                                                               |
      | Games >> Starcraft II       | http://alienwarearena.com/game/sc2/                                                               |
      | Games >> Rift               | http://alienwarearena.com/game/rift/                                                              |
      | Games >> More Games         | /games/                                                                                           |
      | Events                      | http://www.alienwarearena.com/europe/event/                                                       |
      | News >> Articles            | http://www.alienwarearena.com/articles/                                                           |
      | News >> Announcements       | http://www.alienwarearena.com/news/                                                               |
      | Media >> Videos             | http://video.alienwarearena.com/                                                                  |
      | Media >> Wallpapers         | /pages/wallpapers                                                                                 |
      | NewDeals                    | /deal/                                                                                            |
      | NewGroups                   | /groups/                                                                                          |
      | Forums                      | /forums                                                                                           |
      | ARP                         | /arp                                                                                              |
      | Microsoft                   | /microsoft                                                                                        |

  Scenario: The "India" site's main navigation menu has the correct items
    Given I am on the "India" site
    When I go to "/games"
    Then the main navigation menu should be:
      | Link                        | Target                                    |
      | Home                        | /                                         |
      | Alienware                   | http://www.alienware.co.in/               |
      | Games >> Battlefield 3      | http://alienwarearena.com/game/bf3/       |
      | Games >> League of Legends  | http://alienwarearena.com/game/lol/       |
      | Games >> Starcraft II       | http://alienwarearena.com/game/sc2/       |
      | Games >> Rift               | http://alienwarearena.com/game/rift/      |
      | Games >> More Games         | /games/                                   |
      | Events                      | http://www.alienwarearena.com/in/event/   |
      | News >> Articles            | http://www.alienwarearena.com/articles/   |
      | News >> Announcements       | http://www.alienwarearena.com/news/       |
      | Media >> Videos             | http://video.alienwarearena.com/          |
      | Media >> Wallpapers         | /pages/wallpapers                         |
      | Forums                      | /forums                                   |
      | ARP                         | /arp                                      |
      | Microsoft                   | /microsoft                                |

  Scenario: The "Singapore" site's main navigation menu has the correct items
    Given I am on the "Singapore" site
    When I go to "/games"
    Then the main navigation menu should be:
      | Link                        | Target                                    |
      | Home                        | /                                         |
      | Alienware                   | http://allpowerful.com/asia               |
      | Games >> Battlefield 3      | http://alienwarearena.com/game/bf3/       |
      | Games >> League of Legends  | http://alienwarearena.com/game/lol/       |
      | Games >> Starcraft II       | http://alienwarearena.com/game/sc2/       |
      | Games >> Rift               | http://alienwarearena.com/game/rift/      |
      | Games >> More Games         | /games/                                   |
      | Events                      | http://www.alienwarearena.com/sg/event/   |
      | News >> Articles            | http://www.alienwarearena.com/articles/   |
      | News >> Announcements       | http://www.alienwarearena.com/news/       |
      | Media >> Videos             | http://video.alienwarearena.com/          |
      | Media >> Wallpapers         | /pages/wallpapers                         |
      | Forums                      | /forums                                   |
      | ARP                         | /arp                                      |
      | Microsoft                   | /microsoft                                |

  Scenario: The "Australia / New Zealand" site's main navigation menu has the correct items
    Given I am on the "Australia / New Zealand" site
    When I go to "/games"
    Then the main navigation menu should be:
      | Link                        | Target                                    |
      | Home                        | /                                         |
      | Alienware                   | http://www.alienware.com.au/              |
      | Games >> Battlefield 3      | http://alienwarearena.com/game/bf3/       |
      | Games >> League of Legends  | http://alienwarearena.com/game/lol/       |
      | Games >> Starcraft II       | http://alienwarearena.com/game/sc2/       |
      | Games >> Rift               | http://alienwarearena.com/game/rift/      |
      | Games >> More Games         | /games/                                   |
      | Events                      | http://www.alienwarearena.com/anz/event/  |
      | News >> Articles            | http://www.alienwarearena.com/articles/   |
      | News >> Announcements       | http://www.alienwarearena.com/news/       |
      | Media >> Videos             | http://video.alienwarearena.com/          |
      | Media >> Wallpapers         | /pages/wallpapers                         |
      | NewGroups                   | /groups/                                  |
      | Forums                      | /forums                                   |
      | ARP                         | /arp                                      |
      | Microsoft                   | /microsoft                                |
