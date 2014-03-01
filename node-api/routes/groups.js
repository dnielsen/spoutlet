var restify = require('restify'),
    knex    = require('../common').knex,
    resource= require('../resource');

tableName = 'pd_groups';

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
    
var defaultFields = [
    'slug', 
    'name', 
    'category', 
    'description', 
    'created_at', 
    'featured'];

function getTotalCount(resp, callback) {
    knex(tableName).count('*').where({deleted:0}).exec(callback);
};

//exports.getCount = function(req, resp, next) {
//    knex(tableName).count('*').where({deleted:0}).exec(function(err, resultSet) {
//        if (err) {
//            throw new restify.RestError(err);
//        } else if (resultSet === undefined) {
//            new restify.ResourceNotFoundError();
//        }
//        var count = resultSet[0]["count(*)"];
//        resp.header('X-Total-Length',count);
//        resp.send(count);
//    });
//};

exports.findAll = function(req, resp, next) {
    var query = knex(tableName);
    try {
        resource.processCollectionQueryParams(req, query, allowedFields, defaultFields);
    } catch(err) {
        return next(err); 
    }
    
    query.exec(function(err, findAllResultSet) {
        if (err) {
            return next(new restify.RestError(err));
        } else if (findAllResultSet === undefined) {
            return next(new restify.ResourceNotFoundError());
        }
        
        //Set length of results
        resp.header('X-Length', Object.keys(findAllResultSet).length);
        
        //Set length of all results
        getTotalCount(resp, function(err, totalCountResultSet) {
            if (err) {
                throw new restify.RestError(err);
            } else if (totalCountResultSet === undefined) {
                new restify.ResourceNotFoundError();
            }
            var count = totalCountResultSet[0]["count(*)"];
            resp.header('X-Total-Length', count);
            
            resp.send(findAllResultSet);
        });
        
    });
};

exports.findById = function(req, resp, next) {
    var id = req.params.id;
    if (isNaN(id)) {
        return next(new restify.InvalidArgumentError('group id must be a number: '+id));
    }
    
    var query = knex(tableName).where('id',id);
    
    try {
        resource.processBasicQueryParams(req, query, allowedFields, defaultFields);
    } catch(err) {
        return next(err); 
    }
    
    query.exec(function(err, resultSet) {
        if (err) {
            return next(new restify.RestError(err));
        } else if (results === undefined || results.length == 0) {
            return next(new restify.ResourceNotFoundError(id));
        }
        resp.send(resultSet[0]);
    });
};
