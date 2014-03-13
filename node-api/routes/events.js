var Type      = require('../type'),
	Resource  = require('../resource');
    
    
var schema = {    
     "id":                      { type: Type.Int,     props: ["default","read-only"] },
     "attendeeCount":           { type: Type.Int,     props: ["default"] },
     "private":                 { type: Type.Bool, props: ["no-filter"] },
     "name":                    { type: Type.Str,  props: ["required","default"] },
     "slug":                    { type: Type.Str,  props: ["default"] },
     "content":                 { type: Type.Str,  props: ["default","required"] },
     "registration_option":     { type: Type.Str,  props: ["no-filter"] },
     "online":                  { type: Type.Bool, props: [] },
     "starts_at":               { type: Type.Date,    props: ["default"] },
     "ends_at":                 { type: Type.Date,    props: ["default"] },
     "external_url":            { type: Type.Str,  props: ["no-filter"] },
     "location":                { type: Type.Str,  props: [] },
     "address1":                { type: Type.Str,  props: ["default"] },
     "address2":                { type: Type.Str,  props: ["default"] },
     "latitude":                { type: Type.Str,  props: [] },
     "longitude":               { type: Type.Str,  props: [] },
     "created_at":              { type: Type.Date,    props: ["read-only"] },
     "updated_at":              { type: Type.Date,    props: ["read-only"] },
     "currentRound":            { type: Type.Int,     props: ["read-only","no-filter"] },
     
     "group_id":                { type: Type.Group,     props: ["default","required"], mappedBy:'id' },
     "user_id":                 { type: Type.User,     props: ["read-only"], mappedBy:'id' },
     "entrySetRegistration_id": { type: Type.Registry,     props: [], mappedBy:"id" },
 };
    
var resource = new Resource( {
    tableName: 'group_event', 
    schema: schema,
    primary_key:'id',
    user_mapping: ['id','user_id'],
    deleted_col:'deleted'
} );
Type.Event.init(resource);


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
