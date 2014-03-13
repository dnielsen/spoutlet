var Type      = require('../type'),
    Resource  = require('../resource');

Type.Group.Category.init(
    //validator
    function(value) { return value === 'topic' || value === 'location'; },
    //default filter
    function(column, query, value) { query.where(column, value); },
    //prefix_filters
    {});

var get_now = function() { return new Date(); };

var spec = {
    tableName: 'pd_groups', 
    primary_key:'id',
    user_mapping: ['id','owner_id'],
    deleted_col:'deleted',
    schema: {
        "id":                     { type: Type.Int,   rel: "owns", props:  ["read-only","default"] },
        "groupAvatar_id":         { type: Type.Int,   rel: "owns", props:  ["read-only"] },
        "name":                   { type: Type.Str,   rel: "owns", props:  ["default", "required"] },
        "category":               { type: Type.Group.Category,rel:"owns",props:["default"] },
        "description":            { type: Type.Str,   rel: "owns", props:  [] },
        "slug":                   { type: Type.Str,   rel: "owns", props:  ["default", "required"] },
        "featured":               { type: Type.Bool,  rel: "owns", props:  ["default"],                initial: false },
        "isPublic":               { type: Type.Bool,  rel: "owns", props:  [],                         initial: true },
        "created_at":             { type: Type.Date,  rel: "owns", props:  ["read-only"],              initial: get_now },
        "updated_at":             { type: Type.Date,  rel: "owns", props:  ["read-only","no-filter"],  initial: get_now },
        "featured_at":            { type: Type.Date,  rel: "owns", props:  ["read-only","no-filter"],  initial: get_now },
        "entrySetRegistration_id":{ type: Type.Int,   rel: "owns", props:  ["read-only"]},
        "owner_id":               { type: Type.Int,   rel: "owns", props:  ["read-only"]},
        "parentGroup_id":         { type: Type.Int,   rel: "owns", props:  ["parent_id", "read-only"] },

        // "entrySetRegistration":   { type: Type.Registry,rel: "belongs-to", mapping:"entrySetRegistration_id", props:  ["read-only"] },
        // "owner":                  { type: Type.User,    rel: "belongs-to", mapping:"owner_id",                props:  ["read-only"] },
        // "parentGroup":            { type: Type.Group,   rel: "belongs-to", mapping:"parentGroup_id",          props:  ["parent_id", "read-only"] },

        // "subgroups":              { type: Type.Group,   rel: "has-many",   mapping:"parentGroup_id",  limit:10, sort_by:"name" },
        // "events":                 { type: Type.Event,   rel: "has-many",   mapping:"group_id",        limit:10, sort_by:"name" },
    }
};

var resource = new Resource( spec );

Type.Group.init(resource);

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


