var Resource  = require('../resource'),
    Type      = require('../type');

require("./users");
require("./registries");

var category_validator = function(value) { return value === 'topic' || value === 'location'; }
var category_type = new Type(category_validator,
    function(column, query, value) { query.where(column, value); },{});

var schema = {
    "id":                       { type: Type.Int,   props:  ["read-only","default","filterable"] },
    "parentGroup_id":           { type: {},         props:  ["parent_id", "read-only","filterable"], mappedBy:"id"},
    "groupAvatar_id":           { type: Type.Int,   props:  ["read-only","filterable"] },
    "owner_id":                 { type: Type.User,  props:  ["read-only","filterable"], mappedBy:"id" },
    "entrySetRegistration_id":  { type: Type.Registry,props:["read-only","filterable"], mappedBy:"id" },
    "name":                     { type: Type.Str,   props:  ["default", "required", "filterable"] },
    "category":                 { type: category_type,props:["default","filterable"] },
    "description":              { type: Type.Str,   props:  ["filterable"] },
    "slug":                     { type: Type.Str,   props:  ["default", "required","filterable"] },
    "featured":                 { type: Type.Bool,  props:  ["default","filterable"], initial: false },
    "isPublic":                 { type: Type.Bool,  props:  ["filterable"], initial: true },
    "created_at":               { type: Type.Date,  props:  ["read-only","filterable"], initial: function(){return new Date()} },
    "updated_at":               { type: Type.Date,  props:  ["read-only"], initial: function(){return new Date()} },
    "featured_at":              { type: Type.Date,  props:  ["read-only"], initial: function(){return new Date()} },
};

var resource = new Resource( {
    tableName: 'pd_groups', 
    schema: schema,
    primary_key:'id',
    user_mapping: ['id','owner_id'],
    deleted_col:'deleted',
} );

Type.Group = new Resource.ResourceType(resource);
resource.schema.parentGroup_id.type = Type.Group;

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


