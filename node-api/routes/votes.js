var Resource  = require('../resource');
    
var schema = {
    "user":               { type: 'string',props: ["default","required"] },
    "idea":               { type: 'int',   props: ["default","required"] },
};
    
var lists = new Resource( {
    tableName: 'follow_mappings', 
    schema: schema,
    filters: {
        user: { field: 'user', operator: 'like' },
        entry: { field: 'idea', operator: '=' } // idea, session, thread
    }
} );

exports.findAll = function(req, resp, next) { 
    return lists.findAll(req, resp, next); 
}

exports.findById = function(req, resp, next) {
    return lists.findById(req, resp, next); 
}

exports.create = function(req, resp, next) {
    return lists.create(req, resp, next);
}
