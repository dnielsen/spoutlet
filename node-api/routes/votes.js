var Type      = require('../type'),
    Resource  = require('../resource');
    
    
var spec = {
    tableName: 'follow_mappings', 
    primary_key:'idea',
    user_mapping: ['username','user'],
    schema: {
        "user": { type: Type.User,  props: ["default","read-only"], mappedBy:"username" },
        "idea": { type: Type.Entry, props: ["default","required"], mappedBy:'id' }
    }
};
    
var resource = new Resource( spec );
Type.Vote.init(resource);

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
