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

var required = [
    'name', 
    'description', 
    "entrySetRegistration_id",
    ];

var read_only = [
    "id",
    "createdAt",
    "highestRound",
    "creator_id"];
    
var lists = new Resource( {
    tableName: 'entry_set', 
    defaultFields: defaultFields, 
    allowedFields: allowedFields,
    required: required,
    read_only: read_only,
    filters: {
        q: { field: 'name', operator: 'like' },
        type: { field: 'type', operator: 'like' } // idea, session, thread
    }
} );

exports.findAll = function(req, resp, next) { 
    return lists.findAll(req, resp, next); 
}

exports.findById = function(req, resp, next) {
    return lists.findById(req, resp, next); 
}

exports.create = function(req, resp, next) {
    return lists.create(req, resp, next);
}
