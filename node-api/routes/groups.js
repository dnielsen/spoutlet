var Type      = require('../type'),
    Resource  = require('../resource');

Type.Group.Category.init(
    //validator
    function(value) { return value === 'topic' || value === 'location'; },
    //default filter
    function(column, query, value) { query.where(column, value); },
    //prefix_filters
    {});

var schema = {
    "id":                       { type: Type.Int,   props:  ["read-only","default"] },
    "groupAvatar_id":           { type: Type.Int,   props:  ["read-only"] },
    "name":                     { type: Type.Str,   props:  ["default", "required"] },
    "category":                 { type: Type.Group.Category,props:["default"] },
    "description":              { type: Type.Str,   props:  [] },
    "slug":                     { type: Type.Str,   props:  ["default", "required"] },
    "featured":                 { type: Type.Bool,  props:  ["default"], initial: false },
    "isPublic":                 { type: Type.Bool,  props:  [], initial: true },
    "created_at":               { type: Type.Date,  props:  ["read-only"], initial: function(){return new Date()} },
    "updated_at":               { type: Type.Date,  props:  ["read-only","no-filter"], initial: function(){return new Date()} },
    "featured_at":              { type: Type.Date,  props:  ["read-only","no-filter"], initial: function(){return new Date()} },

    "entrySetRegistration_id":  { type: Type.Registry,props:["read-only"], mappedBy:"id" },
    "owner_id":                 { type: Type.User,  props:  ["read-only"], mappedBy:"id" },
    "parentGroup_id":           { type: Type.Group,         props:  ["parent_id", "read-only"], mappedBy:"id"},
};

var resource = new Resource( {
    tableName: 'pd_groups', 
    schema: schema,
    primary_key:'id',
    user_mapping: ['id','owner_id'],
    deleted_col:'deleted',
} );

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


