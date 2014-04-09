var Type = require('../type'),
    Resource = require('../resource'),
    restify = require('restify'),
    common = require('../common'),
    knex = common.knex,
    __ = require('underscore');

Type.Group.Category.init(
    //validator
    function (value) {
        return value === 'topic' || value === 'location';
    },
    //default filter
    function (column, query, value) {
        query.where(column, value);
    },
    //prefix_filters
    {}
);

var get_now = function () {
    return new Date();
};

var spec = {
    tableName : 'pd_groups',
    primary_key : 'id',
    user_mapping : { user : 'id', me : 'owner_id' },
    deleted_col : 'deleted',
    schema : {
        "id" : { type : Type.Int, props : ["read-only", "default"] },
        "groupAvatar_id" : { type : Type.Int, props : ["read-only"] },
        "name" : { type : Type.Str, props : ["default", "required"] },
        "category" : { type : Type.Group.Category, props : ["default"] },
        "description" : { type : Type.Str, props : [] },
        "slug" : { type : Type.Str, props : ["default", "required"] },
        "featured" : { type : Type.Bool, props : ["default"], initial : false },
        "isPublic" : { type : Type.Bool, props : [], initial : true },
        "created_at" : { type : Type.Date, props : ["read-only"], initial : get_now },
        "updated_at" : { type : Type.Date, props : ["read-only", "no-filter"], initial : get_now },
        "featured_at" : { type : Type.Date, props : ["read-only", "no-filter"], initial : get_now },
        "entrySetRegistration_id" : { type : Type.Int, props : ["read-only"]},
        "owner_id" : { type : Type.Int, props : []},
        "parentGroup_id" : { type : Type.Int, props : ["read-only"] },

        "entrySetRegistration" : { type : Type.Registry, rel : "belongs_to", props : ["default"], mapping : "entrySetRegistration_id" },
        "owner" : { type : Type.User, rel : "belongs_to", mapping : "owner_id" },
        "parentGroup" : { type : Type.Group, rel : "belongs_to", mapping : "parentGroup_id" },

        "subgroups" : { type : Type.Group, rel : "has_many", mapping : "parentGroup_id", limit : 10, sort_by : "name" },
        "events" : { type : Type.Event, rel : "has_many", mapping : "group_id", limit : 10, sort_by : "name" },
    }
};

var resource = new Resource(spec);

Type.Group.init(resource);

exports.find_all = function (req, resp, next) {
    return resource.find_all(req, resp, next);
};

exports.find_by_primary_key = function (req, resp, next) {
    return resource.find_by_primary_key(req, resp, next);
};

exports.create = function (req, resp, next) {
    return resource.create(req, resp, next);
};

exports.delete_by_primary_key = function (req, resp, next) {
    return resource.delete_by_primary_key(req, resp, next);
};

var get_events = function (groups, resp, next) {
    var query = knex('group_event')
    .column('id')
    .column('name')
    .column('group_id')
    .column('starts_at')
    .column('ends_at')
    .column('location')
    .orderBy('starts_at', 'desc')
    .where(resource.deleted_col, 0)
    .andWhere('private',0)
    .andWhere(function() {
        for(var i=0; i<groups.length; i++) {
            this.orWhere('group_id', groups[i]);
        }
    });

    query.then( function(result) {
        resp.send(200, result);
        next();
    }).catch(function (e) {
        console.log("Error:" + e);
        return next(new restify.InternalError(e));
    });
};

exports.find_descendants = function (req, resp, next, visitor) {
    var primary_key = parseInt(req.params.id, 10);
    if (!primary_key) {
        throw new restify.InvalidArgumentError("'" + primary_key + "' invalid");
    }

    var groups = [ parseInt(primary_key, 10) ];
    var send_error = function (e) {
        console.log("Error:" + e);
        return next(new restify.InternalError(e));
    };

    var get_descendants_helper = function (responses) {
        var recurse = false;
        var query = knex('pd_groups')
        .column('id')
        .column('parentGroup_id')
        .where(resource.deleted_col, 0)
        .andWhere(function() {
            for(var i=0; i<responses.length; i++) {
                var response = responses[i];
                if(__.contains(groups, response.id)) {
                    console.log("Cycle detected: " + response.id);
                    continue;
                }

                groups.push(response.id);
                this.orWhere('parentGroup_id', response.id);
                recurse=true;
            }
        });

        if(recurse) {
            query.then(get_descendants_helper).catch(send_error);
        } else {
            visitor(groups, resp, next);
        }
    };

    var query = knex('pd_groups').column('id').column('name').column('parentGroup_id')
        .where('parentGroup_id', primary_key);
    query.then(get_descendants_helper).catch(send_error);
};

exports.all_events = function (req, resp, next) {
    return exports.find_descendants(req, resp, next, get_events);
};