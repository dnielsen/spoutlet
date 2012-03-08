set :stages, %w(production beta)
set :stage_dir, "app/config/deploy"
require 'capistrano/ext/multistage'

# set the primary server, then use it to - potentially, have an array of servers
ssh_options[:port] = "22"

set :app1,        "ec2-184-73-162-139.compute-1.amazonaws.com"
set :app2,        "ec2-75-101-175-33.compute-1.amazonaws.com"

set :repository,  "file:///var/www/spoutlet"
set :repository,  "file:///Users/ryan/Sites/clients/Platformd"
set :scm,         :git
set :deploy_via,  :rsync_with_remote_cache
set :user,        "ubuntu"

role :web,        app1, app2                         # Your HTTP server, Apache/etc
role :app,        app1, app2                         # This may be the same as your `Web` server
role :db,         app1, :primary => true       # This is where Rails migrations will run

set  :keep_releases,  3
set  :use_sudo,      false
set :update_vendors, true

# keep the vendor files shared, for faster deployment
set :shared_children,     [app_path + "/logs", web_path + "/uploads", "vendor", web_path + "/media", app_path + "/data", web_path + "/video"]

# share our database configuration
set :shared_files,      ["app/config/parameters.ini"]
