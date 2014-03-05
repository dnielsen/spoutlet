var Resource  = require('../resource');
    
var schema = {
    "id":                      { type: 'int',   props: ["default","read_only"] },
    "entrySetRegistration_id": { type: 'object:entry_set_registration',   props: ["required"] },
    "name":                    { type: 'string',   props: ["default","required"] },
    "type":                    { type: 'string',   props: ["default"] },
    "isVotingActive":          { type: 'boolean',   props: [] },
    "isSubmissionActive":      { type: 'boolean',   props: [] },
    "allowedVoters":           { type: 'string',   props: ["default"] },
    "creator_id":              { type: 'object:user',   props: ["read_only"] },
    "description":             { type: 'string',   props: ["default", "required"] },
};
    
var lists = new Resource( {
    tableName: 'entry_set', 
    schema: schema,
    filters: {
        q: { field: 'name', operator: 'like' },
        type: { field: 'type', operator: 'like' } // idea, session, thread
    }
} );

exports.findAll = function(req, resp, next) { 
    return lists.findAll(req, resp, next); 
}

exports.findById = function(req, resp, next) {
    return lists.findById(req, resp, next); 
}

exports.create = function(req, resp, next) {
    return lists.create(req, resp, next);
}
