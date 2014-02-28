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
