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
server.use(restify.bodyParser( { mapParams: false } ));

//----------------------------------------------------------

server.use(restify.authorizationParser());

var fail_auth = function(res, relm, message) {
	res.header("WWW-Authenticate","Basic realm=\""+common.security_relm_token+"\"");
	res.send(401, message);
}

var get_api_token = function(req, res, next) {
	if(!req.authorization.hasOwnProperty("basic")) {
		fail_auth(res, common.security_relm_login, "No credentials provided");
		return; // no furthar processing, don't call next()
	}

	var req_username = req.authorization.basic.username;
	var req_password = req.authorization.basic.password;

	var return_error = function(err) { return next(new restify.RestError(err)); };

	var validate_user = function(db_user_data_array) { 
		if(db_user_data_array.length === 0) {
			fail_auth(res, common.security_relm_login, "Bad user name");
			return; // no furthar processing, don't call next()
		}
		var db_user_data = db_user_data_array[0];
		
		var new_hashed_password = common.hash_password( req_password, db_user_data.salt );
		var hashed_password = db_user_data.password;
		
		if(hashed_password != new_hashed_password) {
			fail_auth(res, common.security_relm_login, "Bad password");
			return; // no furthar processing, don't call next()
		}

		//Credentials check out, return API key
		res.send(200, db_user_data.uuid);
	};

	common.knex("fos_user").where("username",req.username).then( validate_user, return_error );
};

server.get('/api_key', get_api_token);

//----------------------------------------------------------

var api_token_checker = function(req, res, next) {
	if(!req.authorization.hasOwnProperty("basic")) {
		fail_auth(res, common.security_relm_token, "No API key provided");
		return; // no furthar processing, don't call next()
	}
	var uuid = req.authorization.basic.username;

	if(!common.uuid_regex.test(uuid)) {
		fail_auth(res, common.security_relm_token, "API key is invalid");
		return;
	}

	var return_error = function(err) { return next(new restify.RestError(err)); };
	var save_user = function(user_data) { 
		if(user_data.length === 0) {
			fail_auth(res, common.security_relm_token, "API key was not recognized");
			return;
		}

		//Credentials check out, save the user and continue processing the request
		req.user = user_data[0]; 
		next(); 
	};

	var user_data = common.knex("fos_user").where("uuid",uuid).then( save_user, return_error );
}

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
