* Use the most current AMI.
* Make sure to use the `m1.medium` instance size.
* Check the "Use detailed monitoring" checkbox
* Make sure the instances have the `AWAWebServers` security group.
* Always start up the machine in US-EAST-1C.
* When the machines are up, temporarily edit the deploy script for production so that ONLY the new servers will be deployed to... then deploy (this means that the deploy script won't burden but existing servers, but the new servers will be brought up to date).
* Add the new machines external IPs to the database `awaApi` table that governs which IPs can use the API (for our key).
* Add the new machines to the varnish VCL (in both the BAN and backend sections).
* When you are sure that the servers are ready, `sudo varnishadm` and run `vcl.load unique-identifier /etc/varnish/default.vcl`, replacing "unique-identifier" with something like 000-new-config
* While still in varnishadm, run `vcl.use unique-identifier`, specifiying the identifier you just loaded, then `quit`
* Varnish should now be using the new servers you added - you can check this with the scripts in the `varnish/monitoring_scripts` directory
