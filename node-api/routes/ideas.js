var Resource  = require('../resource');

var defaultFields = [
    'id', 
    'name', 
    'creator_id', 
    //'description', 
    "members",
    "isPrivate",];
    
var allowedFields = [
    "id",
    "entrySet_id",
    "creator_id",
    "image_id",
    "name",
    "createdAt",
    "description",
    "members",
    "highestRound",
    "isPrivate"];

var required = [
    'name', 
    'description', 
    "entrySet_id"];

var read_only = [
    "id",
    "createdAt",
    "highestRound",
    "creator_id"];
    
var idea = new Resource( {
    tableName: 'idea', 
    defaultFields: defaultFields, 
    allowedFields: allowedFields,
    required: required,
    read_only: read_only
} );
    
exports.findAll = function(req, resp, next) { 
    return idea.findAll(req, resp, next); 
}

exports.findById = function(req, resp, next) {
    return idea.findById(req, resp, next); 
}

exports.create = function(req, resp, next) {
    return idea.create(req, resp, next);
}








