var restify  = require('restify'),
    common   = require('./common'),
    ideas    = require('./routes/ideas'),
    lists    = require('./routes/lists'),
    sessions = require('./routes/sessions'),
    events   = require('./routes/events'),
    groups   = require('./routes/groups'),
    votes    = require('./routes/votes');
    
var server = restify.createServer({name: common.baseHost});
//server.pre(restify.pre.userAgentConnection());
server.use(restify.acceptParser(server.acceptable));
server.use(restify.gzipResponse());
//server.use(restify.fullResponse()); //slowish
server.use(restify.queryParser( { mapParams: false } ));
server.use(restify.bodyParser({ mapParams: false }));

server.get('/votes', votes.find_all);
server.get('/votes/:user', votes.find_by_primary_key);
server.post('/votes', votes.create);
// server.patch('/votes/:idea', votes.update);
// server.delete('/votes', votes.delete);

server.get('/ideas', ideas.find_all);
server.get('/ideas/:id', ideas.find_by_primary_key);
server.post('/ideas', ideas.create);

server.get('/lists', lists.find_all);
server.get('/lists/:id', lists.find_by_primary_key);
server.post('/lists', lists.create);

server.get('/sessions', sessions.find_all);
server.get('/sessions/:id', sessions.find_by_primary_key);
server.post('/sessions', sessions.create);

server.get('/events', events.find_all);
server.get('/events/:id', events.find_by_primary_key);
server.post('/events', events.create);

server.get('/groups', groups.find_all);
server.get('/groups/:id', groups.find_by_primary_key);
server.post('/groups', groups.create);

server.listen(common.basePort, function() {
  console.log('%s listening at %s', server.name, server.url);
});
