var Resource  = require('../resource');

var type_validator = function(value) { return value === 'idea' || value === 'session' || value === 'thread'; }

var schema = {
    "id":                      { type: 'int',   props: ["default","read_only"] },
    "entrySetRegistration_id": { type: 'object:entry_set_registration',   props: ["required"] },
    "name":                    { type: 'string',   props: ["default","required"] },
    "type":                    { type: 'string',   props: ["default"], validator: type_validator },
    "isVotingActive":          { type: 'boolean',   props: [] },
    "isSubmissionActive":      { type: 'boolean',   props: [] },
    "allowedVoters":           { type: 'string',   props: ["default"] },
    "creator_id":              { type: 'object:user',   props: ["read_only"] },
    "description":             { type: 'string',   props: ["default", "required"] },
};
    
var resource = new Resource( {
    tableName: 'entry_set', 
    schema: schema,
    primary_key:'id',
    filters: {
        q: { field: 'name', operator: 'like' },
        type: { field: 'type', operator: 'like' } // idea, session, thread
    }
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