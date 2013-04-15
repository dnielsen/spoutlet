@mink:goutte
Feature: Main Navigation
  In order to find my way around the site
  As an user
  I should only see main navigation links to features that are enabled for the site I'm on

  Background:
    Given I am authenticated as a user
    # And my CEVO User ID is 55 # This is just here for reference, don't uncomment, the system is setup with a default user who actually has a CEVO User ID of 55

  Scenario: The "Japan" site's main navigation menu has the correct items
    Given I am on the "Japan" site
    When I go to "/app_test.php/games/"
    Then the main navigation menu should be:
      | Link        | Target                   | Destination                                                |
      | ホーム        | /app_test.php/           | http://japan.alienwarearena.local/app_test.php/            |
      | Alienware   | http://alienware.jp/     |                                                            |
      | イベント       | /app_test.php/events    | http://japan.alienwarearena.local/app_test.php/events     |
      | ニュース       | /app_test.php/news       | http://japan.alienwarearena.local/app_test.php/news        |
      | ビデオ         | /video                  | http://japan.alienwarearena.local/video                    |

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
      | Novedades >> Artículos      | http://www.alienwarearena.com/articles/        |                                                            |
      | Novedades >> Anuncios       | http://www.alienwarearena.com/news/            |                                                            |
      | Medios >> Vídeos            | http://video.alienwarearena.com/               |                                                            |
      | Medios >> Explorar          | /app_test.php/wallpapers                       | http://latam.alienwarearena.local/app_test.php/wallpapers  |
      | Medios >> NewGalería de Fotos   | /app_test.php/galleries/                   | http://latam.alienwarearena.local/app_test.php/galleries/  |
      | Medios >> NewConcursos      | /app_test.php/contests/image                   | http://latam.alienwarearena.local/app_test.php/contests/image   |
      | Foros                       | /app_test.php/forums                           | http://www.alienwarearena.com/forums                       |
      | ARP                         | /app_test.php/arp                              | http://www.alienwarearena.com/arp/sweepstakes/             |
      | Microsoft                   | /app_test.php/microsoft                        | http://latam.alienwarearena.local/app_test.php/microsoft                    |

  Scenario: The "North America" site's main navigation menu has the correct items
    Given I am on the "North America" site
    When I go to "/app_test.php/games/"
    Then the main navigation menu should be:
      | Link                        | Target                                          | Destination                                                         |
      | Home                        | /app_test.php/                                  | http://www.alienwarearena.com/                                      |
      | Alienware                   | http://www.alienware.com/                       |                                                                     |
      | Games >> Battlefield 3      | http://alienwarearena.com/game/bf3/             |                                                                     |
      | Games >> League of Legends  | http://alienwarearena.com/game/lol/             |                                                                     |
      | Games >> Starcraft II       | http://alienwarearena.com/game/sc2/             |                                                                     |
      | Games >> Rift               | http://alienwarearena.com/game/rift/            |                                                                     |
      | Games >> More Games         | /app_test.php/games/                            | http://na.alienwarearena.local/app_test.php/age/verify              |
      | Events                      | /app_test.php/events                            | http://na.alienwarearena.local/app_test.php/events                  |
      | News >> Articles            | http://www.alienwarearena.com/articles/         |                                                                     |
      | News >> Announcements       | http://www.alienwarearena.com/news/             |                                                                     |
      | Media >> Videos             | http://video.alienwarearena.com/                |                                                                     |
      | Media >> Wallpapers         | /app_test.php/wallpapers                        | http://na.alienwarearena.local/app_test.php/wallpapers              |
      | Media >> NewImage Gallery   | /app_test.php/galleries/                        | http://na.alienwarearena.local/app_test.php/galleries/              |
      | Media >> NewContests        | /app_test.php/contests/image                    | http://na.alienwarearena.local/app_test.php/contests/image          |
      | Deals                       | /app_test.php/deal/                             | http://na.alienwarearena.local/app_test.php/deal/                   |
      | Groups >> All Groups        | /app_test.php/groups/                           | http://na.alienwarearena.local/app_test.php/groups/                 |
      | Groups >> My Groups         | /app_test.php/account/profile/groups            | http://na.alienwarearena.local/app_test.php/account/profile/groups  |
      | Groups >> Create Group      | /app_test.php/groups/new/                       | http://na.alienwarearena.local/app_test.php/groups/new/             |
      | Groups >> NewContests       | /app_test.php/contests/group                    | http://na.alienwarearena.local/app_test.php/contests/group          |
      | Forums                      | /app_test.php/forums                            | http://www.alienwarearena.com/forums                                |
      | ARP                         | /app_test.php/arp                               | http://www.alienwarearena.com/arp/sweepstakes/                      |
      | Microsoft                   | /app_test.php/microsoft                         | http://na.alienwarearena.local/app_test.php/microsoft                             |

  Scenario: The "Europe" site's main navigation menu has the correct items
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
      | Events                      | /app_test.php/events                                                                              | http://eu.alienwarearena.local/app_test.php/events      |
      | News >> Articles            | http://www.alienwarearena.com/articles/                                                           |                                                         |
      | News >> Announcements       | http://www.alienwarearena.com/news/                                                               |                                                         |
      | Media >> Videos             | http://video.alienwarearena.com/                                                                  |                                                         |
      | Media >> Wallpapers         | /app_test.php/wallpapers                                                                          | http://eu.alienwarearena.local/app_test.php/wallpapers  |
      | Media >> NewImage Gallery   | /app_test.php/galleries/                                                                          | http://eu.alienwarearena.local/app_test.php/galleries/  |
      | Media >> NewContests        | /app_test.php/contests/image                                                                      | http://eu.alienwarearena.local/app_test.php/contests/image   |
      | Deals                       | /app_test.php/deal/                                                                               | http://eu.alienwarearena.local/app_test.php/deal/       |
      | Groups >> All Groups        | /app_test.php/groups/                                                                             | http://eu.alienwarearena.local/app_test.php/groups/     |
      | Groups >> My Groups         | /app_test.php/account/profile/groups                                                              | http://eu.alienwarearena.local/app_test.php/account/profile/groups |
      | Groups >> Create Group      | /app_test.php/groups/new/                                                                         | http://eu.alienwarearena.local/app_test.php/groups/new/ |
      | Groups >> NewContests       | /app_test.php/contests/group                                                                      | http://eu.alienwarearena.local/app_test.php/contests/group               |
      | Forums                      | /app_test.php/forums                                                                              | http://www.alienwarearena.com/forums                    |
      | ARP                         | /app_test.php/arp                                                                                 | http://www.alienwarearena.com/arp/sweepstakes/          |
      | Microsoft                   | /app_test.php/microsoft                                                                           | http://eu.alienwarearena.local/app_test.php/microsoft                 |

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
      | Microsoft                   | /app_test.php/microsoft                   | http://in.alienwarearena.local/app_test.php/microsoft                  |

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
      | Microsoft                   | /app_test.php/microsoft                   | http://mysg.alienwarearena.local/app_test.php/microsoft                  |

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
      | Microsoft                   | /app_test.php/microsoft                   | http://anz.alienwarearena.local/app_test.php/microsoft                 |
