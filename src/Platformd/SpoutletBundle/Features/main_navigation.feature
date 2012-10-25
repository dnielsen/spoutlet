@mink:goutte
Feature: Main Navigation
  In order to find my way around the site
  As an user
  I should only see main navigation links to features that are enabled for the site I'm on

  Scenario: The "Japan" site's main navigation menu has the correct items
    Given I am on the "Japan" site
    When I go to "/app_test.php/games/"
    Then the main navigation menu should be:
      | Link        | Target                   | Destination                                                |
      | ホーム        | /app_test.php/           | http://japan.alienwarearena.local/app_test.php/            |
      | Alienware   | http://alienware.jp/     |                                                            |
      | イベント       | /app_test.php/events/    | http://japan.alienwarearena.local/app_test.php/events/     |
      | ニュース       | /app_test.php/news       | http://japan.alienwarearena.local/app_test.php/news        |
      | ビデオ         | /video                  | http://japan.alienwarearena.local/video                    |
      | Microsoft   | /app_test.php/microsoft  |  http://japan.alienwarearena.local/app_test.php/microsoft  |

  Scenario: The "China" site's main navigation menu has the correct items
    Given I am on the "China" site
    When I go to "/app_test.php/games/"
    Then the main navigation menu should be:
      | Link              | Target                      | Destination                                                    |
      | 首页              | /app_test.php/               | http://china.alienwarearena.local/app_test.php/               |
      | 关于ALIENWARE     | http://alienware.com.cn/     |                                                               |
      | 活动              | /app_test.php/events/        | http://china.alienwarearena.local/app_test.php/events/        |
      | 新闻              | /app_test.php/news           | http://china.alienwarearena.local/app_test.php/news           |
      | 媒体 >> 视频      |  /video                       | http://china.alienwarearena.local/video                       |
      | 媒体 >> 壁纸下载    | /app_test.php/wallpapers    | http://china.alienwarearena.local/app_test.php/wallpapers      |
      | 微软              | /app_test.php/microsoft      | http://china.alienwarearena.local/app_test.php/microsoft      |

  Scenario: The "Latin America" site's main navigation menu has the correct items
    Given I am on the "Latin America" site
    When I go to "/app_test.php/games/"
    Then the main navigation menu should be:
      | Link                        | Target                                         | Destination                                                |
      | Inicio                      | /app_test.php/                                 | http://www.alienwarearena.com/                             |
      | Alienware                   | http://www.alienware.com/mx/                   |                                                            |
      | Juegos >> Starcraft II      | http://www.alienwarearena.com/latam/game/sc2/  |                                                            |
      | Juegos >> Más Juegos        | /app_test.php/games/                           | http://latam.alienwarearena.local/app_test.php/age/verify  |
      | Eventos                     | http://www.alienwarearena.com/latam/event/     |                                                            |
      | Novedades >> Articles       | http://www.alienwarearena.com/articles/        |                                                            |
      | Novedades >> Announcements  | http://www.alienwarearena.com/news/            |                                                            |
      | Medios >> Vídeos            | http://video.alienwarearena.com/               |                                                            |
      | Medios >> Explorar          | /app_test.php/wallpapers                       | http://latam.alienwarearena.local/app_test.php/wallpapers  |
      | Foros                       | /app_test.php/forums                           | http://www.alienwarearena.com/forums                       |
      | ARP                         | /app_test.php/arp                              | http://www.alienwarearena.com/arp/sweepstakes/             |
      | Microsoft                   | /app_test.php/microsoft                        | http://www.alienwarearena.com/microsoft                    |

  Scenario: The "North America" site's main navigation menu has the correct items
    Given I am on the "North America" site
    When I go to "/app_test.php/games/"
    Then the main navigation menu should be:
      | Link                        | Target                                          | Destination                                             |
      | Home                        | /app_test.php/                                  | http://www.alienwarearena.com/                          |
      | Alienware                   | http://www.alienware.com/                       |                                                         |
      | Games >> Battlefield 3      | http://alienwarearena.com/game/bf3/             |                                                         |
      | Games >> League of Legends  | http://alienwarearena.com/game/lol/             |                                                         |
      | Games >> Starcraft II       | http://alienwarearena.com/game/sc2/             |                                                         |
      | Games >> Rift               | http://alienwarearena.com/game/rift/            |                                                         |
      | Games >> More Games         | /app_test.php/games/                            | http://na.alienwarearena.local/app_test.php/age/verify  |
      | Events                      | http://www.alienwarearena.com/event/            |                                                         |
      | News >> Articles            | http://www.alienwarearena.com/articles/         |                                                         |
      | News >> Announcements       | http://www.alienwarearena.com/news/             |                                                         |
      | SWEEPS                      | /app_test.php/sweepstakes                       | http://www.alienwarearena.com/sweepstakes               |
      | Media >> Videos             | http://video.alienwarearena.com/                |                                                         |
      | Media >> Wallpapers         | /app_test.php/wallpapers                        | http://na.alienwarearena.local/app_test.php/wallpapers  |
      | NewDeals                    | /app_test.php/deal/                             | http://na.alienwarearena.local/app_test.php/deal/       |
      | Forums                      | /app_test.php/forums                            | http://www.alienwarearena.com/forums                    |
      | ARP                         | /app_test.php/arp                               | http://www.alienwarearena.com/arp/sweepstakes/          |
      | Microsoft                   | /app_test.php/microsoft                         | http://www.alienwarearena.com/microsoft                 |

  Scenario: The "Europe" site's main navigatdfion menu has the correct items
    Given I am on the "Europe" site
    When I go to "/app_test.php/games/"
    Then the main navigation menu should be:
      | Link                        | Target                                                                                            | Destination                                             |
      | Home                        | /app_test.php/                                                                                    | http://www.alienwarearena.com/                          |
      | Alienware                   | http://www1.euro.dell.com/content/topics/segtopic.aspx/alienware?c=uk&cs=ukdhs1&l=en&s=dhs&~ck=mn |                                                         |
      | Games >> Battlefield 3      | http://alienwarearena.com/game/bf3/                                                               |                                                         |
      | Games >> League of Legends  | http://alienwarearena.com/game/lol/                                                               |                                                         |
      | Games >> Starcraft II       | http://alienwarearena.com/game/sc2/                                                               |                                                         |
      | Games >> Rift               | http://alienwarearena.com/game/rift/                                                              |                                                         |
      | Games >> More Games         | /app_test.php/games/                                                                              | http://eu.alienwarearena.local/app_test.php/age/verify  |
      | Events                      | http://www.alienwarearena.com/europe/event/                                                       |                                                         |
      | News >> Articles            | http://www.alienwarearena.com/articles/                                                           |                                                         |
      | News >> Announcements       | http://www.alienwarearena.com/news/                                                               |                                                         |
      | Media >> Videos             | http://video.alienwarearena.com/                                                                  |                                                         |
      | Media >> Wallpapers         | /app_test.php/wallpapers                                                                          | http://eu.alienwarearena.local/app_test.php/wallpapers  |
      | NewDeals                    | /app_test.php/deal/                                                                               | http://eu.alienwarearena.local/app_test.php/deal/       |
      | Forums                      | /app_test.php/forums                                                                              | http://www.alienwarearena.com/forums                    |
      | ARP                         | /app_test.php/arp                                                                                 | http://www.alienwarearena.com/arp/sweepstakes/          |
      | Microsoft                   | /app_test.php/microsoft                                                                           | http://www.alienwarearena.com/microsoft                 |

  Scenario: The "India" site's main navigation menu has the correct items
    Given I am on the "India" site
    When I go to "/app_test.php/games/"
    Then the main navigation menu should be:
      | Link                        | Target                                    | Destination                                              |
      | Home                        | /app_test.php/                            | http://www.alienwarearena.com/                           |
      | Alienware                   | http://www.alienware.co.in/               |                                                          |
      | Games >> Battlefield 3      | http://alienwarearena.com/game/bf3/       |                                                          |
      | Games >> League of Legends  | http://alienwarearena.com/game/lol/       |                                                          |
      | Games >> Starcraft II       | http://alienwarearena.com/game/sc2/       |                                                          |
      | Games >> Rift               | http://alienwarearena.com/game/rift/      |                                                          |
      | Games >> More Games         | /app_test.php/games/                      | http://in.alienwarearena.local/app_test.php/age/verify   |
      | Events                      | http://www.alienwarearena.com/in/event/   |                                                          |
      | News >> Articles            | http://www.alienwarearena.com/articles/   |                                                          |
      | News >> Announcements       | http://www.alienwarearena.com/news/       |                                                          |
      | Media >> Videos             | http://video.alienwarearena.com/          |                                                          |
      | Media >> Wallpapers         | /app_test.php/wallpapers                  | http://in.alienwarearena.local/app_test.php/wallpapers   |
      | Forums                      | /app_test.php/forums                      | http://www.alienwarearena.com/forums                     |
      | ARP                         | /app_test.php/arp                         | http://www.alienwarearena.com/arp/sweepstakes/           |
      | Microsoft                   | /app_test.php/microsoft                   | http://www.alienwarearena.com/microsoft                  |

  Scenario: The "Singapore" site's main navigation menu has the correct items
    Given I am on the "Singapore" site
    When I go to "/app_test.php/games/"
    Then the main navigation menu should be:
      | Link                        | Target                                    | Destination                                              |
      | Home                        | /app_test.php/                            | http://www.alienwarearena.com/                           |
      | Alienware                   | http://allpowerful.com/asia               |                                                          |
      | Games >> Battlefield 3      | http://alienwarearena.com/game/bf3/       |                                                          |
      | Games >> League of Legends  | http://alienwarearena.com/game/lol/       |                                                          |
      | Games >> Starcraft II       | http://alienwarearena.com/game/sc2/       |                                                          |
      | Games >> Rift               | http://alienwarearena.com/game/rift/      |                                                          |
      | Games >> More Games         | /app_test.php/games/                      | http://mysg.alienwarearena.local/app_test.php/age/verify |
      | Events                      | http://www.alienwarearena.com/sg/event/   |                                                          |
      | News >> Articles            | http://www.alienwarearena.com/articles/   |                                                          |
      | News >> Announcements       | http://www.alienwarearena.com/news/       |                                                          |
      | Media >> Videos             | http://video.alienwarearena.com/          |                                                          |
      | Media >> Wallpapers         | /app_test.php/wallpapers                  | http://mysg.alienwarearena.local/app_test.php/wallpapers |
      | Forums                      | /app_test.php/forums                      | http://www.alienwarearena.com/forums                     |
      | ARP                         | /app_test.php/arp                         | http://www.alienwarearena.com/arp/sweepstakes/           |
      | Microsoft                   | /app_test.php/microsoft                   | http://www.alienwarearena.com/microsoft                  |

  Scenario: The "Australia / New Zealand" site's main navigation menu has the correct items
    Given I am on the "Australia / New Zealand" site
    When I go to "/app_test.php/games/"
    Then the main navigation menu should be:
      | Link                        | Target                                    | Destination                                             |
      | Home                        | /app_test.php/                            | http://www.alienwarearena.com/                          |
      | Alienware                   | http://www.alienware.com.au/              |                                                         |
      | Games >> Battlefield 3      | http://alienwarearena.com/game/bf3/       |                                                         |
      | Games >> League of Legends  | http://alienwarearena.com/game/lol/       |                                                         |
      | Games >> Starcraft II       | http://alienwarearena.com/game/sc2/       |                                                         |
      | Games >> Rift               | http://alienwarearena.com/game/rift/      |                                                         |
      | Games >> More Games         | /app_test.php/games/                      | http://anz.alienwarearena.local/app_test.php/age/verify |
      | Events                      | http://www.alienwarearena.com/anz/event/  |                                                         |
      | News >> Articles            | http://www.alienwarearena.com/articles/   |                                                         |
      | News >> Announcements       | http://www.alienwarearena.com/news/       |                                                         |
      | Media >> Videos             | http://video.alienwarearena.com/          |                                                         |
      | Media >> Wallpapers         | /app_test.php/wallpapers                  | http://anz.alienwarearena.local/app_test.php/wallpapers |
      | Forums                      | /app_test.php/forums                      | http://www.alienwarearena.com/forums                    |
      | ARP                         | /app_test.php/arp                         | http://www.alienwarearena.com/arp/sweepstakes/          |
      | Microsoft                   | /app_test.php/microsoft                   | http://www.alienwarearena.com/microsoft                 |
