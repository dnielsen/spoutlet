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

var idea = new Resource( {
    tableName: 'idea', 
    defaultFields: defaultFields, 
    allowedFields: allowedFields
} );
    
exports.findAll = function(req, resp, next) { 
    return idea.findAll(req, resp, next); 
}
exports.findById = function(req, resp, next) {
    return idea.findById(req, resp, next); 
}








