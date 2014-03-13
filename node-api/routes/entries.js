var Type      = require('../type'),
    Resource  = require('../resource');
    

var schema = {
    "id":          { type: Type.Int, props: ["default","read_only"] },
    "image_id":    { type: Type.Int, props: ["no-filter"] },
    "name":        { type: Type.Str, props: ["default","required"] },
    "createdAt":   { type: Type.Date, props: ["read_only"] },
    "description": { type: Type.Str, props: ["required"] },
    "members":     { type: Type.Str, props: ["default"] },
    "highestRound":{ type: Type.Int, props: ["read_only"] },
    "isPrivate":   { type: Type.Bool, props: ["default","no-filter"] },

    "entrySet_id": { type: Type.List, props: ["required"], mappedBy:'id' },
    "creator_id":  { type: Type.User, props: ["default","read_only"], mappedBy:'id' },
}; 
var resource = new Resource( {
    tableName: 'idea',
    primary_key:'id',
    user_mapping: ['id','creator_id'],
    schema: schema
} );
Type.Entry.init(resource);

    
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



