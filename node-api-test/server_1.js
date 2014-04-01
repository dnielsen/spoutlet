var restify = require('restify'),
    tv4 = require('tv4'),
    _ = require('underscore');

//Example data, this should really come from a database.
var groups = [
    { "name": "Data Mining", "description": "Big data discussion group" },
    { "name": "Node.js", "description": "javascript everywhere!" },
    { "name": "REST Rocks", "description": "representational state transfer" },
    { "name": "RESTful markup languages", "description": "standard formats rock" }
];

var group_schema = {
    "$schema": "http://json-schema.org/draft-04/schema",
    "type" : "object",
    "properties" : {
        "name" : { "type" : "string", "minLength":1 },
        "description" : { "type" : "string" }
    }
};

var post_handler = function (req, res, next) {
    var requested_group = req.body;
    var return_code, return_value;

    var valid = tv4.validate(requested_group, group_schema);
    if (valid) {
        groups.push(requested_group);
        return_code = 201;
        return_value = "New group created with id=" + groups.indexOf(requested_group);
    } else {
        var err = tv4.error;
        return_code = 400;
        return_value = err.message + " for property" + err.dataPath;
    }

    res.send(return_code, return_value);
    next();
};

//Handle requests from the user. 
//req: contains all the request information
//res: contains the details of our response
//next: allow other handlers to process user request
var get_handler = function (req, res, next) {
    var return_code = 404;
    var return_value = 'group could not be found';

    if (!_.isUndefined(req.params.id)) {
        var id = req.params.id;
        if (_.has(groups, id)) {
            return_value = groups[id];
            return_code = 200;
        }
    } else {
        return_value = groups;
        return_code = 200;
    }
    res.send(return_code, return_value);
    next();
};

//Server object we will attach all our REST calls to
var server = restify.createServer();
server.use(restify.CORS());
server.use(restify.acceptParser('application/json'));
server.use(restify.bodyParser());

//If incoming connection is for <hostname>/groups/<some id number> then call function respond()
server.get('/groups/', get_handler);
server.get('/groups/:id', get_handler);
server.post('/groups', post_handler);

//Start listening for connections
server.listen(3000, function () {
    console.log('%s listening at %s', server.name, server.url);
});