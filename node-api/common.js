var knex = require('knex'),
    crypto   = require('crypto');

var knexInstance = knex.initialize({
    client: 'mysql',
    connection: {
        host    : 'localhost', 
        user    : 'root',
        password: 'sqladmin', 
        database: 'campsite',
        charset : 'utf8'
    }}); 
    
module.exports.knex = knexInstance;

module.exports.baseHost = baseHost = 'localhost';
module.exports.basePort = basePort = 8080;
module.exports.baseProtocol = baseProtocol = 'http';
module.exports.baseUrl = baseProtocol + '://' + baseHost + ':' + basePort;
module.exports.uuid_regex = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
module.exports.security_relm_login = "www.campsite.org";
module.exports.security_relm_token = "api.campsite.org, user:<api-key>, pass:<none>";

var password_algorithm = 'sha512';
var password_iterations = 1;
var password_encoding = 'hex';

module.exports.hash_password = function(password, salt) {
	var merged_pass = password + "{" + salt + "}";
		
	var digest = crypto.createHash(password_algorithm)
		.update(merged_pass)
		.digest();

	for(var i = 1; i < password_iterations; i++) {
		digest = crypto.createHash( algorithm, digest + merged_pass ).digest();
	}

	var new_hashed_password = new Buffer(digest).toString(password_encoding);
	return new_hashed_password;
}