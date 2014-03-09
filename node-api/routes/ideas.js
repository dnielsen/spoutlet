var Resource  = require('../resource'),
    Type      = require('../type');

var schema = {
    "id":          { type: Type.Int, props: ["default","read_only","filterable"] },
    "entrySet_id": { type: Type.Int, props: ["required","filterable"] },
    "creator_id":  { type: Type.Int, props: ["default","read_only","filterable"] },
    "image_id":    { type: Type.Int, props: [""] },
    "name":        { type: Type.Str, props: ["default","required","filterable"] },
    "createdAt":   { type: Type.Date, props: ["read_only","filterable"] },
    "description": { type: Type.Str, props: ["required","filterable"] },
    "members":     { type: Type.Str, props: ["default","filterable"] },
    "highestRound":{ type: Type.Int, props: ["read_only","filterable"] },
    "isPrivate":   { type: Type.Bool, props: ["default"] },
}; 
var resource = new Resource( {
    tableName: 'idea',
    primary_key:'id',
    schema: schema
} );
    
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



