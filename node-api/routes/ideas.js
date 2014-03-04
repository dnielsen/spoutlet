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

var lists = new Resource('idea', defaultFields, allowedFields);

exports.findAll = function(req, resp, next) { 
    return lists.findAll(req, resp, next); 
}
exports.findById = function(req, resp, next) {
    return lists.findById(req, resp, next); 
}








