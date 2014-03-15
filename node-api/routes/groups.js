var Type = require('../type'),
    Resource = require('../resource');

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
    {});

var get_now = function () {
    return new Date();
};

var spec = {
    tableName : 'pd_groups',
    primary_key : 'id',
    user_mapping : ['id','owner_id'],
    deleted_col : 'deleted',
    schema : {
        "id" : { type : Type.Int,  props : ["read-only","default"] },
        "groupAvatar_id" : { type : Type.Int,  props : ["read-only"] },
        "name" : { type : Type.Str,  props : ["default", "required"] },
        "category" : { type : Type.Group.Category,props : ["default"] },
        "description" : { type : Type.Str,  props : [] },
        "slug" : { type : Type.Str,  props : ["default", "required"] },
        "featured" : { type : Type.Bool, props : ["default"],                initial : false },
        "isPublic" : { type : Type.Bool, props : [],                         initial : true },
        "created_at" : { type : Type.Date, props : ["read-only"],              initial : get_now },
        "updated_at" : { type : Type.Date, props : ["read-only","no-filter"],  initial : get_now },
        "featured_at" : { type : Type.Date, props : ["read-only","no-filter"],  initial : get_now },
        "entrySetRegistration_id" : { type : Type.Int,  props : ["read-only"]},
        "owner_id" : { type : Type.Int,  props : ["read-only"]},
        "parentGroup_id" : { type : Type.Int,  props : ["read-only"] },

        "entrySetRegistration" : { type : Type.Registry,rel : "belongs_to", props : ["default"], mapping : "entrySetRegistration_id" },
        "owner" : { type : Type.User,    rel : "belongs_to", mapping : "owner_id" },
        "parentGroup" : { type : Type.Group,   rel : "belongs_to", mapping : "parentGroup_id" },

        "subgroups" : { type : Type.Group,   rel : "has_many",   mapping : "parentGroup_id",  limit : 10, sort_by : "name" },
        "events" : { type : Type.Event,   rel : "has_many",   mapping : "group_id",        limit : 10, sort_by : "name" },
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