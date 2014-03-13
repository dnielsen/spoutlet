var Type      = require('../type'),
    Resource  = require('../resource');
    

var schema = {
    "id":            { type: Type.Int, props: ["read_only", "default","filterable"] },
    "name":          { type: Type.Str, props: ["required", "default","filterable"] },
    "content":       { type: Type.Str, props: ["required","filterable"] },
    "starts_at":     { type: Type.Date, props: ["default","filterable"] },
    "ends_at":       { type: Type.Date, props: ["default"] },
    "date":          { type: Type.Date, props: ["filterable"] },
    
    "event_id":      { type: Type.Event, props: ["required", "default","filterable"], mappedBy:'id' },
    "source_idea_id":{ type: Type.Entry, props: ["filterable"], mappedBy:'id' },
};
    
var resource = new Resource( {
    tableName: 'event_session', 
    primary_key:'id',
    schema: schema,
} );
Type.Session.init(resource);

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






