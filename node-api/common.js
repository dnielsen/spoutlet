var knex = require('knex');

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