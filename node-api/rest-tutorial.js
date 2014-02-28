// Work from here: http://www.nodewiz.biz/nodejs-rest-api-with-mysql-and-express/

var restify  = require('restify'),
    database = require('./database'),
    ideas    = require('./routes/ideas');
    lists    = require('./routes/lists');
    sessions = require('./routes/sessions');
    events   = require('./routes/events');
    groups   = require('./routes/groups');
    
var dbconfig = { 
    host: 'localhost', 
    user: 'root',
    password: 'sqladmin', 
    database: 'campsite'};
   
database.connect(dbconfig);   


var server = restify.createServer({name: 'api.campsite.org'});
server.pre(restify.pre.userAgentConnection());
server.use(restify.fullResponse());
server.use(restify.queryParser());
server.use(restify.bodyParser());

server.get('/ideas', ideas.findAll);
server.get('/ideas/:id', ideas.findById);
//server.post('/ideas', ideas.add);

server.get('/lists', lists.findAll);
server.get('/lists/:id', lists.findById);

server.get('/sessions', sessions.findAll);
server.get('/sessions/:id', sessions.findById);

server.get('/events', events.findAll);
server.get('/events/:id', events.findById);

server.get('/groups', groups.findAll);
server.get('/groups/:id', groups.findById);

server.listen(8080, function() {
  console.log('%s listening at %s', server.name, server.url);
});
