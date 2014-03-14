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

var spec = { 
    tableName: 'entry_set',
    primary_key:'id',
    user_mapping: ['id','creator_id'],
    schema: {
        "id":                      { type: Type.Int,    props: ["default","read_only"] },
        "name":                    { type: Type.Str,    props: ["default","required"] },
        "type":                    { type: Type.List.Type,props: ["default"] },
        "isVotingActive":          { type: Type.Bool,   props: [] },
        "isSubmissionActive":      { type: Type.Bool,   props: [] },
        "allowedVoters":           { type: Type.Str,    props: ["default"] },
        "description":             { type: Type.Str,    props: ["default", "required"] },
        "entrySetRegistration_id": { type: Type.Int,    props: ["default","required"] },
        "creator_id":              { type: Type.Int,    props: ["read_only"] },

        "entrySetRegistration": { type: Type.Registry,rel: "belongs_to", mapping:"entrySetRegistration_id" },
        "creator":              { type: Type.User,    rel: "belongs_to", mapping:"creator_id" },
    }
};
    
var resource = new Resource( spec );
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
