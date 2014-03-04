var Resource  = require('../resource');

var defaultFields = [
    'id', 
    'name', 
    'event_id', 
    //'content', 
    "starts_at",
    "ends_at",];
    
var allowedFields = [
    "id",
    "event_id",
    "name",
    "content",
    "starts_at",
    "ends_at",
    "date",
    "source_idea_id"];


var sessions = new Resource('event_session', defaultFields, allowedFields);

exports.findAll = function(req, resp, next) { 
    return sessions.findAll(req, resp, next); 
}
exports.findById = function(req, resp, next) {
    return sessions.findById(req, resp, next); 
}








