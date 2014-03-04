var Resource  = require('../resource');

var defaultFields = [
    'id', 
    'name', 
    'type', 
    //'description', 
    "allowedVoters",
    "creator_id",];
    
var allowedFields = [
    "id",
    "entrySetRegistration_id",
    "name",
    "type",
    "isVotingActive",
    "isSubmissionActive",
    "allowedVoters",
    "creator_id",
    "description"];

var lists = new Resource( {
    tableName: 'entry_set', 
    defaultFields: defaultFields, 
    allowedFields: allowedFields
} );

exports.findAll = function(req, resp, next) { 
    return lists.findAll(req, resp, next); 
}
exports.findById = function(req, resp, next) {
    return lists.findById(req, resp, next); 
}
