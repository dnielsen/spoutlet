var Resource  = require('../resource');

var schema = {
    "id":          { type: 'int', props: ["default","read_only"] },
    "entrySet_id": { type: 'object:entry_set', props: ["required"] },
    "creator_id":  { type: 'object:user', props: ["default","read_only"] },
    "image_id":    { type: 'object:media', props: [""] },
    "name":        { type: 'string', props: ["default","required"] },
    "createdAt":   { type: 'date', props: ["read_only"] },
    "description": { type: 'string', props: ["required"] },
    "members":     { type: 'string', props: ["default"] },
    "highestRound":{ type: 'int', props: ["read_only"] },
    "isPrivate":   { type: 'boolean', props: ["default"] },
}; 
var idea = new Resource( {
    tableName: 'idea',
    primary_key:'id',
    schema: schema
} );
    
exports.find_all = function(req, resp, next) { 
    return idea.find_all(req, resp, next); 
}

exports.find_by_primary_key = function(req, resp, next) {
    return idea.find_by_primary_key(req, resp, next); 
}

exports.create = function(req, resp, next) {
    return idea.create(req, resp, next);
}








