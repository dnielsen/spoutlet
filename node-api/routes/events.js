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

var event = new Resource( {
    tableName: 'group_event', 
    defaultFields: defaultFields, 
    allowedFields: allowedFields, 
    deleted_col:'deleted'
} );

exports.findAll = function(req, resp, next) { 
    return event.findAll(req, resp, next); 
}
exports.findById = function(req, resp, next) {
    return event.findById(req, resp, next); 
}

