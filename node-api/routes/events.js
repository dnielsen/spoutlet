var Resource  = require('../resource');
    
var schema = {    
     "id":                      { type: 'int',     props: ["read-only"] },
     "group_id":                { type: 'int',     props: ["required"] },
     "user_id":                 { type: 'int',     props: ["read-only"] },
     "attendeeCount":           { type: 'int',     props: ["default"] },
     "private":                 { type: 'boolean', props: [] },
     "name":                    { type: 'string',  props: ["required","default"] },
     "slug":                    { type: 'string',  props: ["default"] },
     "content":                 { type: 'string',  props: ["required"] },
     "registration_option":     { type: 'string',  props: [] },
     "online":                  { type: 'boolean', props: [] },
     "starts_at":               { type: 'date',    props: [] },
     "ends_at":                 { type: 'date',    props: [] },
     "external_url":            { type: 'string',  props: [] },
     "location":                { type: 'string',  props: [] },
     "address1":                { type: 'string',  props: ["default"] },
     "address2":                { type: 'string',  props: ["default"] },
     "latitude":                { type: 'string',  props: [] },
     "longitude":               { type: 'string',  props: [] },
     "created_at":              { type: 'date',    props: ["read-only"] },
     "updated_at":              { type: 'date',    props: ["read-only"] },
     "currentRound":            { type: 'int',     props: ["read-only"] },
     "entrySetRegistration_id": { type: 'int',     props: [] },
 };
    
var event = new Resource( {
    tableName: 'group_event', 
    schema: schema,
    primary_key:'id',
    deleted_col:'deleted'
} );

exports.find_all = function(req, resp, next) { 
    return event.find_all(req, resp, next); 
}
exports.find_by_primary_key = function(req, resp, next) {
    return event.find_by_primary_key(req, resp, next); 
}

exports.create = function(req, resp, next) {
    return event.create(req, resp, next);
}
