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

//----------------------------------------------------------

var api_token_checker = function(req, res, next) {
	var uuid = req.header("Api-Token");
	
	if(typeof uuid === "undefined")
		return next(new restify.NotAuthorizedError("Missing Api-Token"));

	if(!common.uuid_regex.test(uuid))
		return next(new restify.InvalidHeaderError("Api-Token format invalid"));

	var return_error = function(err) { return next(new restify.RestError(err)); };
	var print_user = function(user_data) { 
		if(user_data.length === 0)
			return next(new restify.InvalidCredentialsError("Api-Token is not recognized"));

		//Attach the user to the request for furthar processing
		req.user = user_data[0]; 
		next(); 
	};

	var user_data = common.knex("fos_user").where("uuid",uuid).then( print_user, return_error );
};
server.use(api_token_checker);

//----------------------------------------------------------

server.get('/votes', votes.find_all);
server.post('/votes', votes.create);
server.get('/votes/:idea', votes.find_by_primary_key);
// server.patch('/votes/:idea', votes.update);
server.del('/votes/:idea', votes.delete_by_primary_key);

server.get('/ideas', ideas.find_all);
server.post('/ideas', ideas.create);
server.get('/ideas/:id', ideas.find_by_primary_key);
server.del('/ideas/:id', ideas.delete_by_primary_key);

server.get('/lists', lists.find_all);
server.post('/lists', lists.create);
server.get('/lists/:id', lists.find_by_primary_key);
server.del('/lists/:id', lists.delete_by_primary_key);

server.get('/sessions', sessions.find_all);
server.post('/sessions', sessions.create);
server.get('/sessions/:id', sessions.find_by_primary_key);
server.del('/sessions/:id', sessions.delete_by_primary_key);

server.get('/events', events.find_all);
server.post('/events', events.create);
server.get('/events/:id', events.find_by_primary_key);
server.del('/events/:id', events.delete_by_primary_key);

server.get('/groups', groups.find_all);
server.post('/groups', groups.create);
server.get('/groups/:id', groups.find_by_primary_key);
server.del('/groups/:id', groups.delete_by_primary_key);

server.listen(common.basePort, function() {
  console.log('%s listening at %s', server.name, server.url);
});
