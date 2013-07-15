set :stages, %w(production staging migration)
set :stage_dir, "app/config/deploy"
require 'capistrano/ext/multistage'

ssh_options[:port] = "22"
ssh_options[:forward_agent] = true
default_run_options[:pty] = true

set :awaProcessor1, "ec2-75-101-139-101.compute-1.amazonaws.com"

set :awaWeb1, "ec2-54-227-65-32.compute-1.amazonaws.com"
set :awaWeb2, "ec2-23-20-93-253.compute-1.amazonaws.com"
set :awaWeb3, "ec2-54-227-94-218.compute-1.amazonaws.com"
set :awaWeb4, "ec2-23-20-212-246.compute-1.amazonaws.com"
set :awaWeb5, "ec2-50-16-39-141.compute-1.amazonaws.com"

set :campWeb1, "ec2-54-235-26-82.compute-1.amazonaws.com"

set :scm,         :git
set :repository,  "git@github.com:platformd/spoutlet.git"
set :user,        "ubuntu"
set :branch,      "master"

role :web,        awaProcessor1, awaWeb1, awaWeb2, awaWeb3, awaWeb4, awaWeb5, campWeb1
role :app,        awaProcessor1, awaWeb1, awaWeb2, awaWeb3, awaWeb4, awaWeb5, campWeb1

role :db,         awaProcessor1, :primary => true

set :keep_releases,  3
set :use_sudo,      false
set :update_vendors, true
set :vendors_mode,   "install"
set :dump_assetic_assets, true

set :shared_children,     [app_path + "/logs", web_path + "/uploads", "vendor", web_path + "/media", app_path + "/data", web_path + "/media", "misc_scripts/flock_files"]
set :shared_files,      ["app/config/parameters.ini", "app/config/config_server.yml"]

# After finalizing update - update translations
after "deploy:finalize_update" do
  run "cd #{latest_release} && #{php_bin} #{symfony_console} spoutlet:translations:entity-extract",:once => true
end

# Change ownership of releases directories to allow cleanup without permissions issues
after "deploy:create_symlink" do
  run "sudo chown -R `whoami`:`whoami` #{deploy_to}/releases/"
end

before "symfony:assets:install" do
  run "cd #{latest_release} && #{php_bin} #{symfony_console} themes:update"
  run "cd #{latest_release} && #{php_bin} #{symfony_console} themes:install web --symlink"
end

after "deploy", "deploy:cleanup"

namespace :deploy do
  desc "Write the date to a VERSION file"
  task :write_version_file do
    set :dump_date, "#{Time.new.year}#{Time.new.month}#{Time.new.day}#{Time.new.hour}#{Time.new.min}#{Time.new.sec}"
    run "echo -n \"#{dump_date}\" > #{release_path}/VERSION"
  end
end

before "deploy:finalize_update", "deploy:write_version_file"
