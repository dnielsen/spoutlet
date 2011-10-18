set :stages, %w(production beta)
set :stage_dir, "app/config/deploy"
require 'capistrano/ext/multistage'


set :domain,      "184.73.162.139"
ssh_options[:port] = "22"

set :repository,  "file:///Users/ryan/Sites/clients/Platformd"
set :scm,         :git
set :deploy_via,  :rsync_with_remote_cache
set :user,        "ubuntu"

role :web,        domain                         # Your HTTP server, Apache/etc
role :app,        domain                         # This may be the same as your `Web` server
role :db,         domain, :primary => true       # This is where Rails migrations will run

set  :keep_releases,  3
set  :use_sudo,      false
set :update_vendors, true

# keep the vendor files shared, for faster deployment
set :shared_children,     [app_path + "/logs", web_path + "/uploads", "vendor", web_path + "/media", app_path + "/data"]

# share our database configuration
set :shared_files,      ["app/config/parameters.ini"]
