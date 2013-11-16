#
# Cookbook Name:: campsite
# Recipe:: default
#
# Copyright 2013, YOUR_COMPANY_NAME
#
# All rights reserved - Do Not Redistribute
#

# Download other tools
include_recipe "apache2"
include_recipe "mysql"
include_recipe "mysql::client"
include_recipe "mysql::server"
include_recipe "memcached"
# include_recipe "php"
# include_recipe "php::module_common"
# include_recipe "php::module_apc"
# include_recipe "php::module_memcache"
include_recipe "apache2::mod_php5"
include_recipe "mysql::ruby"
include_recipe "git"

# Configure apache
web_app 'campsite' do
  template 'site.conf.erb'
  docroot node['campsite']['project_path']
  server_name node['campsite']['server_name']
end

apache_site "default" do
  enable false
end

# # Clean up PHP install
# execute "Fix disabled functions in php.ini" do
#   command 'sed -i "s/\(disable_functions = *\).*/\1/" /etc/php5/cli/php.ini'
# end

# execute "Fix memory in php.ini" do
#   command 'sed -i "s/\(memory_limit = *\).*/\1-1/" /etc/php5/cli/php.ini'
# end


#Configure the site 
# template node['campsite']['project_path'] + '/app/config/parameters.ini' do
#   Chef::Log.info("Creating parameters.ini")
#   source 'parameters.ini.erb'
#   mode 0777
#   variables(
#     :database        => node['campsite']['database'],
#     :user            => node['campsite']['db_username'],
#     :password        => node['campsite']['db_password']
#     )
# end

# execute "Update vendors" do
#   Chef::Log.info("Updating vendors")
#   cwd node['campsite']['project_path']

#   user "vagrant"
#   group "vagrant"
#   command "php bin/vendors install"

#   notifies :run, "execute[Create database]", :delayed
#   # notifies :run, "execute[Copy in Resources]", :delayed
#   # notifies :run, "execute[Install themes]", :delayed
#   # notifies :run, "execute[Refresh assets]", :delayed
#   notifies :run, "execute[Cache warmup]", :delayed
#   action :nothing
# end



# Database configuration
execute "Create database" do
  Chef::Log.info("Creating Database")
  cwd node['campsite']['project_path']
  command "php app/console doc:data:create"
  notifies :run, "execute[Generate database tables]", :delayed
end

execute "Generate database tables" do
  cwd node['campsite']['project_path']
  Chef::Log.info("Migrating database from begining of time...")
  command "php app/console doc:mig:mig --no-interaction"
  notifies :query, "mysql_database[#{node['campsite']['database']}]", :delayed
end

# execute "Create admin user" do
#   cwd node['campsite']['project_path']
#   Chef::Log.info("Creating initial user admin/admin")
#   command "php app/console fos:user:create --super-admin admin admin@localhost.com admin"
#   notifies :query, "mysql_database[#{node['campsite']['database']}]", :delayed
# end

mysql_database node['campsite']['database'] do
  connection(
    :host     => 'localhost',
    :username => node['campsite']['db_username'],
    :password => node['mysql']['server_root_password']
  )
  sql        { ::File.open('/home/vagrant/campsite_sites.sql').read }
  action     :query
end


# execute "Refresh assets" do
#   user "vagrant"
#   group "vagrant"
#   cwd node['campsite']['project_path']
#   command "php app/console assetic:dump --env=prod --no-debug; php app/console assets:install web --symlink"
# end

# execute "Install themes" do
#   user "vagrant"
#   group "vagrant"
#   cwd node['campsite']['project_path']
#   command "php app/console themes:install web --symlink"
# end

# execute "Copy in Resources" do
#   user "vagrant"
#   group "vagrant"
#   cwd node['campsite']['project_path']
#   command 'if [ "$( ls /home/vagrant/app_resources )" ]; then cp -r /home/vagrant/app_resources/* app/Resources; fi;'
# end

execute "Cache warmup" do
  user "vagrant"
  group "vagrant"
  cwd node['campsite']['project_path']
  command "php app/console cache:clear"
end
