set :stages, %w(production beta)
set :stage_dir, "app/config/deploy"
require 'capistrano/ext/multistage'

# set the primary server, then use it to - potentially, have an array of servers
ssh_options[:port] = "22"
ssh_options[:forward_agent] = true
default_run_options[:pty] = true

set :app1,  "ec2-54-242-133-224.compute-1.amazonaws.com"
set :app2,  "ec2-23-23-32-176.compute-1.amazonaws.com"
set :app3,  "ec2-54-234-66-252.compute-1.amazonaws.com"
set :app4,  "ec2-23-20-94-62.compute-1.amazonaws.com"
set :app5,  "ec2-54-242-218-154.compute-1.amazonaws.com"
set :app6,  "ec2-54-234-78-4.compute-1.amazonaws.com"
set :app7,  "ec2-23-22-232-27.compute-1.amazonaws.com"
set :app8,  "ec2-54-242-192-234.compute-1.amazonaws.com"
set :app9,  "ec2-23-22-36-86.compute-1.amazonaws.com"

set :repository,  "file:///Users/weaverryan/Sites/clients/spoutlet"

set :scm,         :git
set :repository,  "git@github.com:platformd/spoutlet.git"
set :user,        "ubuntu"
# branch can be overridden in any of the "stage" files (e.g. beta)
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

# After finalizing update - here to update translations
after "deploy:finalize_update" do

  # temporarily not doing this, until first deploy, so we can migrate first
  run "cd #{latest_release} && #{php_bin} #{symfony_console} spoutlet:translations:entity-extract"

end

after "deploy:create_symlink" do

  run "sudo chown -R `whoami`:`whoami` #{deploy_to}/releases/"

end

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

