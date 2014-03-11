var Resource  = require('../resource'),
    Type      = require('../type');
    
require('./ideas');

var schema = {
    "user":               { type: Type.Str,   props: ["default","read-only","filterable"] },
    "idea":               { type: Type.Entry,   props: ["default","required","filterable"], mappedBy:'id' },
};
    
var resource = new Resource( {
    tableName: 'follow_mappings', 
    schema: schema,
    primary_key:'idea',
    user_mapping: ['username','user'],
} );
Type.Vote = new Resource.ResourceType(resource);

exports.find_all = function(req, resp, next) { 
    return resource.find_all(req, resp, next); 
}

exports.find_by_primary_key = function(req, resp, next) {
    return resource.find_by_primary_key(req, resp, next); 
}

exports.create = function(req, resp, next) {
    return resource.create(req, resp, next);
}

exports.delete_by_primary_key = function(req, resp, next) {
    return resource.delete_by_primary_key(req, resp, next); 
}
