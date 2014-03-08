var Resource  = require('../resource');

var category_validator = function(value) { return value === 'topic' || value === 'location'; }

var schema = {
    "id":                       { type: 'int',            props: ["read-only","default"] },
    "parentGroup_id":           { type: 'object:group',   props: ["read-only"] },
    "groupAvatar_id":           { type: 'object:media',   props: ["read-only"] },
    "owner_id":                 { type: 'object:user',    props: ["read-only"] },
    "entrySetRegistration_id":  { type: 'object:entry_set_registration', props: ["read-only"] },
    "name":                     { type: 'object:group',   props: ["default", "required"] },
    "category":                 { type: 'string',         props: ["default"], validator: category_validator },
    "description":              { type: 'string',         props: ["default"] },
    "slug":                     { type: 'string',         props: ["default", "required"] },
    "featured":                 { type: 'boolean',            initial: false },
    "isPublic":                 { type: 'boolean',            initial: true },
    "created_at":               { type: 'date',           props: ["read-only"], initial: function(){return new Date()} },
    "updated_at":               { type: 'date',           props: ["read-only"], initial: function(){return new Date()} },
    "featured_at":              { type: 'date',           props: ["read-only"], initial: function(){return new Date()} },
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