// Work from here: http://www.nodewiz.biz/nodejs-rest-api-with-mysql-and-express/

var restify  = require('restify'),
    common   = require('./common'),
    ideas    = require('./routes/ideas');
    lists    = require('./routes/lists');
    sessions = require('./routes/sessions');
    events   = require('./routes/events');
    groups   = require('./routes/groups');
    
//database.connect(dbconfig);   
//knex.instance('pd_groups');

var server = restify.createServer({name: common.baseHost});
//server.pre(restify.pre.userAgentConnection());
server.use(restify.acceptParser(server.acceptable));
server.use(restify.gzipResponse());
//server.use(restify.fullResponse()); //slowish
server.use(restify.queryParser( { mapParams: false } ));
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
//server.get('/groups/count', groups.getCount);
server.get('/groups/:id', groups.findById);

server.listen(common.basePort, function() {
  console.log('%s listening at %s', server.name, server.url);
});
