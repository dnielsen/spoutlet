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
      | ニュース       | /app_test.php/news       | http://japan.alienwarearena.local/app_test.php/news        |
      | イベント BETA  | /app_test.php/events    | http://japan.alienwarearena.local/app_test.php/events     |
      | ビデオ         | /video                  | http://japan.alienwarearena.local/video                    |
      | Alienware   | http://alienware.jp/     |                                                            |

  Scenario: The "Latin America" site's main navigation menu has the correct items
    Given I am on the "Latin America" site
    When I go to "/app_test.php/games/"
    Then the main navigation menu should be:
      | Link                        | Target                                         | Destination                                                |
      | Inicio                      | /app_test.php/                                 | http://www.alienwarearena.com/                             |
      | Novedades >> Artículos      | http://www.alienwarearena.com/articles/        |                                                            |
      | Novedades >> Anuncios       | http://www.alienwarearena.com/news/            |                                                            |
      | ARP                         | /app_test.php/arp                              | http://www.alienwarearena.com/arp/sweepstakes/             |
      | Juegos                      | /app_test.php/games/                           | http://latam.alienwarearena.local/app_test.php/age/verify  |
      | Eventos                     | http://www.alienwarearena.com/event/           |                                                            |
      | Foros                       | /app_test.php/forums                           | http://www.alienwarearena.com/forums                       |
      | Medios >> NewConcursos      | /app_test.php/contests                         | http://latam.alienwarearena.local/app_test.php/contests    |
      | Medios >> NewGalería de Fotos   | /app_test.php/galleries/                   | http://latam.alienwarearena.local/app_test.php/galleries/  |
      | Medios >> Vídeos            | http://video.alienwarearena.com/               |                                                            |
      | Medios >> Explorar          | /app_test.php/wallpapers                       | http://latam.alienwarearena.local/app_test.php/wallpapers  |
      | Alienware                   | http://www.alienware.com/mx/                   |                                                            |
      | Microsoft                   | /app_test.php/microsoft                        | http://latam.alienwarearena.local/app_test.php/microsoft                    |

  Scenario: The "North America" site's main navigation menu has the correct items
    Given I am on the "North America" site
    When I go to "/app_test.php/games/"
    Then the main navigation menu should be:
      | Link                        | Target                                          | Destination                                                         |
      | Home                        | /app_test.php/                                  | http://www.alienwarearena.com/                                      |
      | News >> Articles            | http://www.alienwarearena.com/articles/         |                                                                     |
      | News >> Announcements       | http://www.alienwarearena.com/news/             |                                                                     |
      | Deals                       | /app_test.php/deal/                             | http://na.alienwarearena.local/app_test.php/deal/                   |
      | ARP                         | /app_test.php/arp                               | http://www.alienwarearena.com/arp/sweepstakes/                      |
      | Groups >> All Groups        | /app_test.php/groups/                           | http://na.alienwarearena.local/app_test.php/groups/                 |
      | Groups >> Create Group      | /app_test.php/groups/new/                       | http://na.alienwarearena.local/app_test.php/groups/new/             |
      | Games                       | /app_test.php/games/                            | http://na.alienwarearena.local/app_test.php/age/verify              |
      | Events                      | http://www.alienwarearena.com/event/            |                                                                     |
      | Forums                      | /app_test.php/forums                            | http://www.alienwarearena.com/forums                                |
      | Media >> NewContests        | /app_test.php/contests                          | http://na.alienwarearena.local/app_test.php/contests                |
      | Media >> NewImage Gallery   | /app_test.php/galleries/                        | http://na.alienwarearena.local/app_test.php/galleries/              |
      | Media >> Videos             | http://video.alienwarearena.com/                |                                                                     |
      | Media >> Wallpapers         | /app_test.php/wallpapers                        | http://na.alienwarearena.local/app_test.php/wallpapers              |
      | Alienware                   | http://www.alienware.com/                       |                                                                     |
      | Microsoft                   | /app_test.php/microsoft                         | http://na.alienwarearena.local/app_test.php/microsoft                             |

  Scenario: The "Europe" site's main navigation menu has the correct items
    Given I am on the "Europe" site
    When I go to "/app_test.php/games/"
    Then the main navigation menu should be:
      | Link                        | Target                                                                                            | Destination                                             |
      | Home                        | /app_test.php/                                                                                    | http://www.alienwarearena.com/                          |
      | News >> Articles            | http://www.alienwarearena.com/articles/                                                           |                                                         |
      | News >> Announcements       | http://www.alienwarearena.com/news/                                                               |                                                         |
      | Deals                       | /app_test.php/deal/                                                                               | http://eu.alienwarearena.local/app_test.php/deal/       |
      | ARP                         | /app_test.php/arp                                                                                 | http://www.alienwarearena.com/arp/sweepstakes/          |
      | Groups >> All Groups        | /app_test.php/groups/                                                                             | http://eu.alienwarearena.local/app_test.php/groups/     |
      | Groups >> Create Group      | /app_test.php/groups/new/                                                                         | http://eu.alienwarearena.local/app_test.php/groups/new/ |
      | Games                       | /app_test.php/games/                                                                              | http://eu.alienwarearena.local/app_test.php/age/verify  |
      | Events                      | http://www.alienwarearena.com/event/                                                              |                                                         |
      | Forums                      | /app_test.php/forums                                                                              | http://www.alienwarearena.com/forums                    |
      | Media >> NewContests        | /app_test.php/contests                                                                            | http://eu.alienwarearena.local/app_test.php/contests    |
      | Media >> NewImage Gallery   | /app_test.php/galleries/                                                                          | http://eu.alienwarearena.local/app_test.php/galleries/  |
      | Media >> Videos             | http://video.alienwarearena.com/                                                                  |                                                         |
      | Media >> Wallpapers         | /app_test.php/wallpapers                                                                          | http://eu.alienwarearena.local/app_test.php/wallpapers  |
      | Alienware                   | http://www1.euro.dell.com/content/topics/segtopic.aspx/alienware?c=uk&cs=ukdhs1&l=en&s=dhs&~ck=mn |                                                         |
      | Microsoft                   | /app_test.php/microsoft                                                                           | http://eu.alienwarearena.local/app_test.php/microsoft                 |

  Scenario: The "India" site's main navigation menu has the correct items
    Given I am on the "India" site
    When I go to "/app_test.php/games/"
    Then the main navigation menu should be:
      | Link                        | Target                                    | Destination                                              |
      | Home                        | /app_test.php/                            | http://www.alienwarearena.com/                           |
      | News >> Articles            | http://www.alienwarearena.com/articles/   |                                                          |
      | News >> Announcements       | http://www.alienwarearena.com/news/       |                                                          |
      | ARP                         | /app_test.php/arp                         | http://www.alienwarearena.com/arp/sweepstakes/           |
      | Games                       | /app_test.php/games/                      | http://in.alienwarearena.local/app_test.php/age/verify   |
      | Events                      | http://www.alienwarearena.com/event/      |                                                          |
      | Forums                      | /app_test.php/forums                      | http://www.alienwarearena.com/forums                     |
      | Media >> Videos             | http://video.alienwarearena.com/          |                                                          |
      | Media >> Wallpapers         | /app_test.php/wallpapers                  | http://in.alienwarearena.local/app_test.php/wallpapers   |
      | Alienware                   | http://www.alienware.co.in/               |                                                          |
      | Microsoft                   | /app_test.php/microsoft                   | http://in.alienwarearena.local/app_test.php/microsoft                  |

  Scenario: The "Singapore" site's main navigation menu has the correct items
    Given I am on the "Singapore" site
    When I go to "/app_test.php/games/"
    Then the main navigation menu should be:
      | Link                        | Target                                    | Destination                                              |
      | Home                        | /app_test.php/                            | http://www.alienwarearena.com/                           |
      | News >> Articles            | http://www.alienwarearena.com/articles/   |                                                          |
      | News >> Announcements       | http://www.alienwarearena.com/news/       |                                                          |
      | ARP                         | /app_test.php/arp                         | http://www.alienwarearena.com/arp/sweepstakes/           |
      | Games                       | /app_test.php/games/                      | http://mysg.alienwarearena.local/app_test.php/age/verify |
      | Events                      | http://www.alienwarearena.com/event/      |                                                          |
      | Forums                      | /app_test.php/forums                      | http://www.alienwarearena.com/forums                     |
      | Media >> Videos             | http://video.alienwarearena.com/          |                                                          |
      | Media >> Wallpapers         | /app_test.php/wallpapers                  | http://mysg.alienwarearena.local/app_test.php/wallpapers |
      | Alienware                   | http://allpowerful.com/asia               |                                                          |
      | Microsoft                   | /app_test.php/microsoft                   | http://mysg.alienwarearena.local/app_test.php/microsoft                  |

  Scenario: The "Australia / New Zealand" site's main navigation menu has the correct items
    Given I am on the "Australia / New Zealand" site
    When I go to "/app_test.php/games/"
    Then the main navigation menu should be:
      | Link                        | Target                                    | Destination                                             |
      | Home                        | /app_test.php/                            | http://www.alienwarearena.com/                          |
      | News >> Articles            | http://www.alienwarearena.com/articles/   |                                                         |
      | News >> Announcements       | http://www.alienwarearena.com/news/       |                                                         |
      | ARP                         | /app_test.php/arp                         | http://www.alienwarearena.com/arp/sweepstakes/          |
      | Games                       | /app_test.php/games/                      | http://anz.alienwarearena.local/app_test.php/age/verify |
      | Events                      | http://www.alienwarearena.com/event/      |                                                         |
      | Forums                      | /app_test.php/forums                      | http://www.alienwarearena.com/forums                    |
      | Media >> Videos             | http://video.alienwarearena.com/          |                                                         |
      | Media >> Wallpapers         | /app_test.php/wallpapers                  | http://anz.alienwarearena.local/app_test.php/wallpapers |
      | Alienware                   | http://www.alienware.com.au/              |                                                         |
      | Microsoft                   | /app_test.php/microsoft                   | http://anz.alienwarearena.local/app_test.php/microsoft                 |
