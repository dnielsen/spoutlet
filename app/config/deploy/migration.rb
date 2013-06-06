role :web,        app1, app2, app3, app4, app5, app6, app7, app8, app9
role :app,        app1, app2, app3, app4, app5, app6, app7, app8, app9

set :deploy_to,   "/var/www/migration/deploy"
set :branch,      "migration"
