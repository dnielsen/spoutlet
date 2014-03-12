var Resource  = require('../resource'),
    Type      = require('../type');

require("./registries");

var type_validator = function(value) { return (value === 'idea' || value === 'session' || value === 'thread'); };
var type_type = new Type(type_validator,
    function(column, query, value) { query.where(column, value); },{});

var schema = {
    "id":                      { type: Type.Int,     props: ["default","read_only","filterable"] },
    "entrySetRegistration_id": { type: Type.Registry,props: ["default","required","filterable"], mappedBy:"id" },
    "name":                    { type: Type.Str,     props: ["default","required","filterable"] },
    "type":                    { type: type_type,    props: ["default","filterable"] },
    "isVotingActive":          { type: Type.Bool,    props: ["filterable"] },
    "isSubmissionActive":      { type: Type.Bool,    props: ["filterable"] },
    "allowedVoters":           { type: Type.Str,     props: ["default","filterable"] },
    "creator_id":              { type: Type.Int,     props: ["read_only","filterable"] },
    "description":             { type: Type.Str,     props: ["default", "required","filterable"] },
};
    
var resource = new Resource( {
    tableName: 'entry_set', 
    schema: schema,
    primary_key:'id',
    user_mapping: ['id','creator_id'],
} );
Type.List = new Resource.ResourceType(resource);

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
