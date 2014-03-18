var Type = require('../type'),
    Resource = require('../resource'),
    common = require('../common'),
    knex = common.knex,
    __ = require('underscore');

Type.List.Type.init(
    //validator
    function (value) {
        return (value === 'idea' || value === 'session' || value === 'thread');
    },
    //default operator
    function (column, query, value) {
        query.where(column, value);
    },
    //prefix operator
    {}
);

var spec = {
    tableName : 'entry_set',
    primary_key : 'id',
    user_mapping : { user : 'id', me : 'creator_id'},
    schema : {
        "id" : { type : Type.Int, props : ["default", "read_only"] },
        "name" : { type : Type.Str, props : ["default", "required"] },
        "type" : { type : Type.List.Type, props : ["default"] },
        "isVotingActive" : { type : Type.Bool, props : [] },
        "isSubmissionActive" : { type : Type.Bool, props : [] },
        "allowedVoters" : { type : Type.Str, props : ["default"] },
        "description" : { type : Type.Str, props : ["default", "required"] },
        "entrySetRegistration_id" : { type : Type.Int, props : ["default", "required"] },
        "creator_id" : { type : Type.Int, props : ["read_only"] },

        "entrySetRegistration" : { type : Type.Registry, rel : "belongs_to", mapping : "entrySetRegistration_id", props : ["default"] },
        "creator" : { type : Type.User, rel : "belongs_to", mapping : "creator_id" },
    }
};

var resource = new Resource(spec);
Type.List.init(resource);

var attach_size = function (req, query) {
    var subtable = "SELECT entrySet_id, count(entrySet_id) as size FROM campsite.idea GROUP BY entrySet_id";
    var table_name = "(" + subtable + ") as list_size";
    var lhs = "list_size.entrySet_id";
    var rhs = resource.tableName + '.' + resource.primary_key;

    query.join(knex.raw(table_name), lhs, '=', rhs, 'left').column("list_size.size");

    //use size as default sort, overridden by user provided sort
    if(!__.has(req.query, 'sort_by'))
        query.orderBy('size', 'DESC');
};

exports.find_all = function (req, resp, next) {
    
    var scope_block_filter;
    //strip out the quotes, add new param, then add quotes back
    if (__.has(req.query, 'entrySetRegistration')) {
        /*jslint regexp: true*/
        scope_block_filter = req.query.entrySetRegistration.replace(/['|"]([^'"]*)['|"]/, "'$1,scope=!~site'");
    } else {
        scope_block_filter = "'scope=!~site'";
    }
    req.query.entrySetRegistration = scope_block_filter;

    return resource.find_all(req, resp, next, attach_size);
};

exports.find_by_primary_key = function (req, resp, next) {
    return resource.find_by_primary_key(req, resp, next, attach_size);
};

exports.create = function (req, resp, next) {
    return resource.create(req, resp, next);
};

exports.delete_by_primary_key = function (req, resp, next) {
    return resource.delete_by_primary_key(req, resp, next);
};

exports.find_popular = function (req, resp, next) {
    knex('idea')
        .join('entry_set', 'entry_set.id', '=', 'idea.entrySet_id')
        .select('entry_set.id', 'entry_set.name')
        .count('idea.id AS popularity')
        .groupBy('entry_set.id')
        .orderBy('popularity', 'DESC')
        .then(function (results) {
            resp.send(results);
            next();
            return;
        });
};

exports.get_sorted_entries = function (req, resp, next) {

    var query = knex('follow_mappings')
        .join('idea', 'idea.id', '=', 'follow_mappings.idea')
        .select('idea.id', 'idea.name', 'idea.entrySet_id')
        .count('follow_mappings.idea AS popularity')
        .groupBy('follow_mappings.idea')
        .orderBy('popularity', 'DESC')
        .where('idea.entrySet_id', req.params.id);

    query.then(function (results) {
        resp.send(results);
        next();
        return;
    });
};