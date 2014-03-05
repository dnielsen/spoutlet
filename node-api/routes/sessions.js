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

var required = [
    'name', 
    "content",
    "event_id"];

var read_only = ["id"];
    
var sessions = new Resource( {
    tableName: 'event_session', 
    defaultFields: defaultFields, 
    allowedFields: allowedFields,
    required: required,
    read_only: read_only,
} );

exports.findAll = function(req, resp, next) { 
    return sessions.findAll(req, resp, next); 
}

exports.findById = function(req, resp, next) {
    return sessions.findById(req, resp, next); 
}

exports.create = function(req, resp, next) {
    return sessions.create(req, resp, next);
}







