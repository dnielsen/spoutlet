var Resource  = require('../resource');
    
var schema = {
    "user":               { type: 'string',props: ["default","required"] },
    "idea":               { type: 'int',   props: ["default","required"] },
};
    
var lists = new Resource( {
    tableName: 'follow_mappings', 
    schema: schema,
    primary_key:'user',
    filters: {
        user: { field: 'user', operator: 'like' },
        entry: { field: 'idea', operator: '=' } // idea, session, thread
    }
} );

exports.find_all = function(req, resp, next) { 
    return lists.find_all(req, resp, next); 
}

exports.find_by_primary_key = function(req, resp, next) {
    return lists.find_by_primary_key(req, resp, next); 
}

exports.create = function(req, resp, next) {
    return lists.create(req, resp, next);
}

// exports.delete = function(req, resp, next) {
//     return lists.delete(req, resp, next);
// }
