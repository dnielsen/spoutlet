var restify = require('restify'),
    util    = require('../util');
    knex    = require('../common').knex;

var tableName = 'pd_groups';
var allowedFields = [
    "id",
    "parentGroup_id",
    "groupAvatar_id",
    "owner_id",
    "entrySetRegistration_id",
    "name", 
    "category",
    "description",
    "slug",
    "featured",
    "isPublic",
    "created_at",
    "updated_at",
    "featured_at"];
    
var defaultFields = ['slug', 'name', 'category', 'description', 'created_at', 'featured'];
    
function processFields( query, req ) {
    var reqFields = req.query.fields.split(',');
    for(i in reqFields) {
        var field = reqFields[i];
        if(allowedFields.indexOf(field) === -1) {
            throw new restify.InvalidArgumentError(field);
        }
    }
    query = query.select(reqFields);
}

function processBasicQueryParams(req, query) {
    if(req.query.hasOwnProperty('fields')) {
        processFields( query, req );
    } else if(req.query.hasOwnProperty('verbose')) {
        query = query.select(allowedFields);
    } else {
        query = query.select(defaultFields);
    }
}

exports.findAll = function(req, res, next) {
    var query = knex(tableName);
    try {
        processBasicQueryParams(req, query);
    } catch(err) {
        return next(err); 
    }
    
    query.exec(function(err, resp) {
        if (err) {
            return next(new restify.RestError(err));
        } else if (resp === undefined) {
            return next(new restify.ResourceNotFoundError());
        }
        res.send(resp);
    });
};

exports.findById = function(req, res, next) {
    var id = req.params.id;
    if (isNaN(id)) {
        return next(new restify.InvalidArgumentError('group id must be a number: '+id));
    }
    
    var query = knex(tableName).where('id',id);
    
    try {
        processBasicQueryParams(req, query);
    } catch(err) {
        return next(err); 
    }
    
    query.exec(function(err, results) {
        if (err) {
            return next(new restify.RestError(err));
        } else if (results === undefined || results.length == 0) {
            return next(new restify.ResourceNotFoundError(id));
        }
        res.send(results[0]);
    });
};
