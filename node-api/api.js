var restify = require('restify'),
    common = require('./common'),
    entries = require('./routes/entries'),
    lists = require('./routes/lists'),
    sessions = require('./routes/sessions'),
    events = require('./routes/events'),
    groups = require('./routes/groups'),
    votes = require('./routes/votes'),
    registries = require('./routes/registries'),
    users = require('./routes/users');

var fail_auth = function (res, relm, message) {
    res.header("WWW-Authenticate", "Basic realm=\"" + relm + "\"");
    res.send(401, message);
};

var get_api_token = function (req, res, next) {
    if (!req.authorization.hasOwnProperty("basic")) {
        fail_auth(res, common.security_relm_login, "No credentials provided");
        return; // no furthar processing, don't call next()
    }

    var req_username = req.authorization.basic.username;
    var req_password = req.authorization.basic.password;

    var return_error = function (err) {
        return next(new restify.RestError(err));
    };

    var validate_user = function (db_user_data_array) {
        if (db_user_data_array.length === 0) {
            fail_auth(res, common.security_relm_login, "Bad user name");
            return; // no furthar processing, don't call next()
        }
        var db_user_data = db_user_data_array[0];

        var new_hashed_password = common.hash_password(req_password, db_user_data.salt);
        var hashed_password = db_user_data.password;

        if (hashed_password !== new_hashed_password) {
            fail_auth(res, common.security_relm_login, "Bad password");
            return; // no furthar processing, don't call next()
        }

        var rv = { id : db_user_data.id, username : db_user_data.username, api_key : db_user_data.uuid };

        //Credentials check out, return API key
        res.send(200, rv);
    };

    common.knex("fos_user").where("username", req_username).then(validate_user, return_error);
};

var api_token_checker = function (req, res, next) {
    if (!req.authorization.hasOwnProperty("basic")) {
        fail_auth(res, common.security_relm_token, "No API key provided");
        return; // no furthar processing, don't call next()
    }
    var uuid = req.authorization.basic.username;

    if (!common.uuid_regex.test(uuid)) {
        fail_auth(res, common.security_relm_token, "API key is invalid");
        return;
    }

    var return_error = function (err) {
        return next(new restify.RestError(err));
    };
    var save_user = function (user_data) {
        if (user_data.length === 0) {
            fail_auth(res, common.security_relm_token, "API key was not recognized");
            return;
        }

        //Credentials check out, save the user and continue processing the request
        req.user = user_data[0];
        next();
    };

    common.knex("fos_user").where("uuid", uuid).then(save_user, return_error);
};

var server = restify.createServer({
    name : common.baseHost
});

//process the response formats user-agent accepts
server.use(restify.acceptParser(server.acceptable));

//If user-agent talks gzip use that 
server.use(restify.gzipResponse());

//parse request's query parameters into req.query
server.use(restify.queryParser({
    mapParams : false
}));

//parse request's body into req.body
server.use(restify.bodyParser({
    mapParams : false
}));

//parse request's authorization headers (or URI user/pass) into req.authorization
server.use(restify.authorizationParser());

//Respond to jsonp requests with Content-Type : application/javascript
server.use(restify.jsonp());

server.use(restify.CORS({
    origins: ['m.campsite.org', 'www.campsite.org', 'campsite.org'],   // defaults to ['*']
    credentials: true                  // defaults to false
}));

//-------------------------  Anonymous calls here  ---------------------------------

server.get('/api_key', get_api_token);

server.get('/users', users.find_all);
server.get('/users/:id', users.find_by_primary_key);

server.get('/registries', registries.find_all);
server.get('/registries/:id', registries.find_by_primary_key);

server.get('/votes', votes.find_all);
server.get('/votes/:idea', votes.find_by_primary_key);

server.get('/entries', entries.find_all);
server.get('/entries/:id', entries.find_by_primary_key);

server.get('/lists', lists.find_all);
server.get('/lists/popular', lists.find_popular);
server.get('/lists/:id/sorted', lists.get_sorted_entries);
server.get('/lists/:id', lists.find_by_primary_key);

server.get('/sessions', sessions.find_all);
server.get('/sessions/:id', sessions.find_by_primary_key);

server.get('/events', events.find_all);
server.get('/events/:id', events.find_by_primary_key);

server.get('/groups', groups.find_all);
server.get('/groups/:id', groups.find_by_primary_key);

//---------------------------------------------------------------------------------------------------
// Token checker will fail any request, with 401 Unauthorized, which does not provide a valid API-Key
// Therefore all request handlers defined below this call are secured calls. 
// All handlers defined above are anonymous calls and do not require an api token.
//---------------------------------------------------------------------------------------------------

server.use(api_token_checker);

//------------------------  Secured calls here  ----------------------------------

server.post('/registries', registries.create);
server.del('/registries/:id', registries.delete_by_primary_key);

server.post('/votes', votes.create);
// server.patch('/votes/:idea', votes.update);
server.del('/votes/:idea', votes.delete_by_primary_key);

server.post('/entries', entries.create);
server.del('/entries/:id', entries.delete_by_primary_key);

server.post('/lists', lists.create);
server.del('/lists/:id', lists.delete_by_primary_key);

server.post('/sessions', sessions.create);
server.del('/sessions/:id', sessions.delete_by_primary_key);

server.post('/events', events.create);
server.del('/events/:id', events.delete_by_primary_key);

server.post('/groups', groups.create);
server.del('/groups/:id', groups.delete_by_primary_key);

//----------------------  Start the server  --------------------------------------

server.listen(common.basePort, function () {
    console.log('%s listening at %s', server.name, server.url);
});