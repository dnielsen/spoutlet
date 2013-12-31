name             'campsite'
maintainer       'PlatformD'
maintainer_email 'eric@platformd.com'
license          'All rights reserved'
description      'Installs/Configures cloudcamp'
long_description IO.read(File.join(File.dirname(__FILE__), 'README.md'))
version          '0.1.0'

depends "apache2"
depends "mysql"
# depends "php"
depends "memcached"
depends "database"
depends "git"
depends "partial_search"
depends "ssh_known_hosts"