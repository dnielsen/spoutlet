var Type      = require('../type'),
    Resource  = require('../resource'),
    common    = require('../common'),
    knex      = common.knex;

Type.List.Type.init(
    //validator
    function(value) { return (value === 'idea' || value === 'session' || value === 'thread'); },
    //default operator
    function(column, query, value) { query.where(column, value); },
    //prefix operator
    {});

var schema = {
    "id":                      { type: Type.Int,     props: ["default","read_only","filterable"] },
    "entrySetRegistration_id": { type: Type.Registry,props: ["default","required","filterable"], mappedBy:"id" },
    "name":                    { type: Type.Str,     props: ["default","required","filterable"] },
    "type":                    { type: Type.List.Type,    props: ["default","filterable"] },
    "isVotingActive":          { type: Type.Bool,    props: ["filterable"] },
    "isSubmissionActive":      { type: Type.Bool,    props: ["filterable"] },
    "allowedVoters":           { type: Type.Str,     props: ["default","filterable"] },
    "creator_id":              { type: Type.User,    props: ["read_only","filterable"], mappedBy:"id" },
    "description":             { type: Type.Str,     props: ["default", "required","filterable"] },
};
    
var resource = new Resource( {
    tableName: 'entry_set',
    schema: schema,
    primary_key:'id',
    user_mapping: ['id','creator_id'],
} );
Type.List.init(resource);

exports.find_all = function(req, resp, next) {
    return resource.find_all(req, resp, next);
};

exports.find_by_primary_key = function(req, resp, next) {
    return resource.find_by_primary_key(req, resp, next);
};

exports.create = function(req, resp, next) {
    return resource.create(req, resp, next);
};

exports.delete_by_primary_key = function(req, resp, next) {
    return resource.delete_by_primary_key(req, resp, next);
};

exports.find_popular = function(req, resp, next) {

    var query = knex('idea')
        .join('entry_set', 'entry_set.id', '=', 'idea.entrySet_id')
        .select('entry_set.id', 'entry_set.name')
        .count('idea.id AS popularity')
        .groupBy('entry_Set.id')
        .orderBy('popularity', 'DESC')
        .then(function(results){
            resp.send(results);
            next();
            return;
        });
};
