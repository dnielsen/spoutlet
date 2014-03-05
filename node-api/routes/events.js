var Resource  = require('../resource');

var defaultFields = [
    'slug', 
    'name', 
    //'content', 
    'attendeeCount', 
    'address1', 
    'address2'];
    
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
    
    deleted_col:'deleted'
} );

exports.findAll = function(req, resp, next) { 
    return event.findAll(req, resp, next); 
}
exports.findById = function(req, resp, next) {
    return event.findById(req, resp, next); 
}

exports.create = function(req, resp, next) {
    return event.create(req, resp, next);
}
