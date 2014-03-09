var Resource  = require('../resource'),
    Type      = require('../type');

//TODO: reattach custom validators
var category_validator = function(value) { return value === 'topic' || value === 'location'; }
var category_type = new Type(category_validator);

var schema = {
    "id":                       { type: Type.Int,  props: ["read-only","default","filterable"] },
    "parentGroup_id":           { type: Type.Int,  props: ["read-only"] },
    "groupAvatar_id":           { type: Type.Int,  props: ["read-only"] },
    "owner_id":                 { type: Type.Int,  props: ["read-only"] },
    "entrySetRegistration_id":  { type: Type.Int,  props: ["read-only"] },
    "name":                     { type: Type.Str,  props: ["default", "required"] },
    "category":                 { type: category_type, props: ["default","filterable"] },
    "description":              { type: Type.Str,  props: ["default","filterable"] },
    "slug":                     { type: Type.Str,  props: ["default", "required"] },
    "featured":                 { type: Type.Bool, props: ["default","filterable"], initial: false },
    "isPublic":                 { type: Type.Bool,  initial: true },
    "created_at":               { type: Type.Date, props: ["read-only","filterable"], initial: function(){return new Date()} },
    "updated_at":               { type: Type.Date, props: ["read-only"], initial: function(){return new Date()} },
    "featured_at":              { type: Type.Date, props: ["read-only"], initial: function(){return new Date()} },
};


var resource = new Resource( {
    tableName: 'pd_groups', 
    schema: schema,
    primary_key:'id',
    deleted_col:'deleted',
    filters: {
        q: { field: 'name', operator: 'like' },
        type: { field: 'category', operator: '=' } //location, topic
    }
} );

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