var Resource  = require('../resource'),
    Type      = require('../type');
    
var schema = {
    "user":               { type: Type.Str,   props: ["default","required","filterable"] },
    "idea":               { type: Type.Int,   props: ["default","required","filterable"] },
};
    
var resource = new Resource( {
    tableName: 'follow_mappings', 
    schema: schema,
    primary_key:'idea',
    user_mapping: ['username','user'],
    filters: {
        user: { field: 'user', operator: 'like' },
        entry: { field: 'idea', operator: '=' } // idea, session, thread
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
