var Resource  = require('../resource');

var defaultFields = [
    'slug', 
    'name', 
    //'content', 
    'attendeeCount', 
    'address1', 
    'address2'];
    
var allowedFields = [    
     "id",
     "group_id",
     "user_id",
     "attendeeCount",
     "private",
     "name",
     "slug",
     "content",
     "registration_option",
     "online",
     "starts_at",
     "ends_at",
     "external_url",
     "location",
     "address1",
     "latitude",
     "longitude",
     "created_at",
     "updated_at",
     "address2",
     "currentRound",
     "entrySetRegistration_id"];

var required = [
    'name', 
    'content', 
    "group_id"];

var read_only = [
    "id",
    "user_id",
    "created_at",
    "updated_at",
    "currentRound"];
    
var event = new Resource( {
    tableName: 'group_event', 
    defaultFields: defaultFields, 
    allowedFields: allowedFields, 
    required: required,
    read_only: read_only,
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
