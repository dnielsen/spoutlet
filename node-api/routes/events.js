var Resource  = require('../resource'),
    Type      = require('../type');
    
var schema = {    
     "id":                      { type: Type.Int,     props: ["default","read-only","filterable"] },
     "group_id":                { type: Type.Int,     props: ["default","required","filterable"] },
     "user_id":                 { type: Type.Int,     props: ["read-only","filterable"] },
     "attendeeCount":           { type: Type.Int,     props: ["default","filterable"] },
     "private":                 { type: Type.Bool, props: [] },
     "name":                    { type: Type.Str,  props: ["required","default","filterable"] },
     "slug":                    { type: Type.Str,  props: ["default","filterable"] },
     "content":                 { type: Type.Str,  props: ["default","required","filterable"] },
     "registration_option":     { type: Type.Str,  props: [] },
     "online":                  { type: Type.Bool, props: ["filterable"] },
     "starts_at":               { type: Type.Date,    props: ["default","filterable"] },
     "ends_at":                 { type: Type.Date,    props: ["default","filterable"] },
     "external_url":            { type: Type.Str,  props: [] },
     "location":                { type: Type.Str,  props: ["filterable"] },
     "address1":                { type: Type.Str,  props: ["default","filterable"] },
     "address2":                { type: Type.Str,  props: ["default","filterable"] },
     "latitude":                { type: Type.Str,  props: ["filterable"] },
     "longitude":               { type: Type.Str,  props: [,"filterable"] },
     "created_at":              { type: Type.Date,    props: ["read-only","filterable"] },
     "updated_at":              { type: Type.Date,    props: ["read-only","filterable"] },
     "currentRound":            { type: Type.Int,     props: ["read-only"] },
     "entrySetRegistration_id": { type: Type.Int,     props: ["filterable"] },
 };
    
var resource = new Resource( {
    tableName: 'group_event', 
    schema: schema,
    primary_key:'id',
    user_mapping: ['id','user_id'],
    deleted_col:'deleted'
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
