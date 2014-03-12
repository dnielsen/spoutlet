var Resource  = require('../resource'),
    Type      = require('../type');

var schema = {
    "id": { type: Type.Int, props: ["default", "filterable", "read-only"]},
    "scope": { type: Type.Str, props: ["default", "filterable", "required"]},
    "containerId": { type: Type.Int, props: ["default", "filterable", "required"]},
};
    
var resource = new Resource( {
    tableName: 'entry_set_registry', 
    schema: schema,
    primary_key:'id'
} );
Type.Registry = new Resource.ResourceType(resource);

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
