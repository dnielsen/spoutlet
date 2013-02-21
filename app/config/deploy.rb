set :stages, %w(production staging)
set :stage_dir, "app/config/deploy"
require 'capistrano/ext/multistage'

# set the primary server, then use it to - potentially, have an array of servers
ssh_options[:port] = "22"
ssh_options[:forward_agent] = true
default_run_options[:pty] = true

set :app1, "ec2-174-129-86-236.compute-1.amazonaws.com"
set :app2, "ec2-54-242-2-165.compute-1.amazonaws.com"
set :app3, "ec2-23-20-100-179.compute-1.amazonaws.com"
set :app4, "ec2-67-202-58-217.compute-1.amazonaws.com"
set :app5, "ec2-54-234-107-140.compute-1.amazonaws.com"
set :app6, "ec2-54-234-176-214.compute-1.amazonaws.com"
set :app7, "ec2-50-16-102-100.compute-1.amazonaws.com"
set :app8, "ec2-107-22-30-169.compute-1.amazonaws.com"
set :app9, "ec2-54-234-184-156.compute-1.amazonaws.com"

set :repository,  "file:///Users/weaverryan/Sites/clients/spoutlet"

set :scm,         :git
set :repository,  "git@github.com:platformd/spoutlet.git"
set :user,        "ubuntu"
# branch can be overridden in any of the "stage" files (e.g. staging)
set :branch,      "master"

role :web,        app1, app2, app3, app4, app5, app6, app7, app8, app9                         # Your HTTP server, Apache/etc
role :app,        app1, app2, app3, app4, app5, app6, app7, app8, app9                         # This may be the same as your `Web` server
role :db,         app1, :primary => true       # This is where Rails migrations will run

set  :keep_releases,  3
set  :use_sudo,      false
set :update_vendors, true
set :vendors_mode,   "install"

# keep the vendor files shared, for faster deployment
set :shared_children,     [app_path + "/logs", web_path + "/uploads", "vendor", web_path + "/media", app_path + "/data", web_path + "/video", web_path + "/media"]

# share our database configuration
set :shared_files,      ["app/config/parameters.ini"]

# After finalizing update - update translations
after "deploy:finalize_update" do
  run "cd #{latest_release} && #{php_bin} #{symfony_console} spoutlet:translations:entity-extract"
end

# Change ownership of releases directories to allow cleanup without permissions issues
after "deploy:create_symlink" do
  run "sudo chown -R `whoami`:`whoami` #{deploy_to}/releases/"
end

# Cleanup releases to leave only the 3 most recent
after "deploy", "deploy:cleanup"

# Custom recipes
namespace :deploy do
  desc "Write the date to a VERSION file"
  task :write_version_file do
    # Nice interactive thing, but not really necessary
    #set(:dump_date) do
    #  Capistrano::CLI.ui.ask("Enter date for version: ") {|q| q.default = "#{Time.new.year}#{Time.new.month}#{Time.new.day}#{Time.new.hour}#{Time.new.min}#{Time.new.sec}" }
    #end

    # instead of the interactive
    set :dump_date, "#{Time.new.year}#{Time.new.month}#{Time.new.day}#{Time.new.hour}#{Time.new.min}#{Time.new.sec}"
    run "echo -n \"#{dump_date}\" > #{release_path}/VERSION"
  end
end

before "deploy:finalize_update", "deploy:write_version_file"

