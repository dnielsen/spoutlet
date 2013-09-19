* Use the most current AMI.
* Make sure to use the m1.medium instance size.
* Make sure the instances have the `AWAWebServers` security group.
* Always start up the machine in US-EAST-1C.
* When the machines are up, temporarily edit the deploy script for production so that ONLY the new servers will be deployed to... then deploy (this means that the deploy script won't burden but existing servers, but the new servers will be brought up to date).
* Add the new machines external IPs to the database `awaApi` table that governs which IPs can use the API (for our key).
* Add the new machines to the varnish VCL (in both the BAN and backend sections).
