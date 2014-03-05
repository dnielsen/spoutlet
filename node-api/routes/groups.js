var Resource  = require('../resource');

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
    "featured":                 { type: 'boolean',            initial: "false" },
    "isPublic":                 { type: 'boolean',            initial: "true" },
    "created_at":               { type: 'date',           props: ["read-only"], initial: ":now" },
    "updated_at":               { type: 'date',           props: ["read-only"], initial: ":now" },
    "featured_at":              { type: 'date',           props: ["read-only"], initial: ":now" },
};

var category_validator = function() { return val === 'topic' || val === 'location'; }

var group = new Resource( {
    tableName: 'pd_groups', 
    schema: schema,
    deleted_col:'deleted',
    filters: {
        q: { field: 'name', operator: 'like' },
        type: { field: 'category', operator: '=' } //location, topic
    }
} );

exports.findAll = function(req, resp, next) { 
    return group.findAll(req, resp, next); 
}

exports.findById = function(req, resp, next) {
    return group.findById(req, resp, next); 
}

exports.create = function(req, resp, next) {
    return group.create(req, resp, next);
}
