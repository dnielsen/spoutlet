var Resource  = require('../resource');
    
var schema = {
    "id":            { type: 'int', props: ["read_only", "default"] },
    "event_id":      { type: 'object:event', props: ["required", "default"] },
    "name":          { type: 'string', props: ["required", "default"] },
    "content":       { type: 'string', props: ["required"] },
    "starts_at":     { type: 'date', props: ["default"] },
    "ends_at":       { type: 'date', props: ["default"] },
    "date":          { type: 'date', props: [""] },
    "source_idea_id":{ type: 'idea', props: [""] },
};
    
var resource = new Resource( {
    tableName: 'event_session', 
    primary_key:'id',
    schema: schema,
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






