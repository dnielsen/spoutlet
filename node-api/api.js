var restify  = require('restify'),
    common   = require('./common'),
    ideas    = require('./routes/ideas'),
    lists    = require('./routes/lists'),
    sessions = require('./routes/sessions'),
    events   = require('./routes/events'),
    groups   = require('./routes/groups');
    
var server = restify.createServer({name: common.baseHost});
//server.pre(restify.pre.userAgentConnection());
server.use(restify.acceptParser(server.acceptable));
server.use(restify.gzipResponse());
//server.use(restify.fullResponse()); //slowish
server.use(restify.queryParser( { mapParams: false } ));
server.use(restify.bodyParser({ mapParams: false }));

//server.get('/ideas', ideas.findAll);
//server.get('/ideas/:id', ideas.findById);
//server.post('/ideas', ideas.create);

//server.get('/lists', lists.findAll);
//server.get('/lists/:id', lists.findById);
//server.post('/lists', lists.create);

//server.get('/sessions', sessions.findAll);
//server.get('/sessions/:id', sessions.findById);
//server.post('/sessions', sessions.create);

server.get('/events', events.findAll);
server.get('/events/:id', events.findById);
server.post('/events', events.create);

server.get('/groups', groups.findAll);
server.get('/groups/:id', groups.findById);
server.post('/groups', groups.create);

server.listen(common.basePort, function() {
  console.log('%s listening at %s', server.name, server.url);
});
