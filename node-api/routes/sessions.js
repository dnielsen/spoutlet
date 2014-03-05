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
    
var sessions = new Resource( {
    tableName: 'event_session', 
    schema: schema,
} );

exports.findAll = function(req, resp, next) { 
    return sessions.findAll(req, resp, next); 
}

exports.findById = function(req, resp, next) {
    return sessions.findById(req, resp, next); 
}

exports.create = function(req, resp, next) {
    return sessions.create(req, resp, next);
}







