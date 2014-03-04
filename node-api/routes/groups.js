var Resource  = require('../resource');

var allowedFields = [
    "id",
    "parentGroup_id",
    "groupAvatar_id",
    "owner_id",
    "entrySetRegistration_id",
    "name", 
    "category",
    "description",
    "slug",
    "featured",
    "isPublic",
    "created_at",
    "updated_at",
    "featured_at"];
    
var defaultFields = [
    'slug', 
    'name', 
    'category', 
    'description', 
    'created_at', 
    'featured'];

var group = new Resource( {
    tableName: 'pd_groups', 
    defaultFields: defaultFields, 
    allowedFields: allowedFields, 
    deleted_col:'deleted'
} );

exports.findAll = function(req, resp, next) { 
    return group.findAll(req, resp, next); 
}
exports.findById = function(req, resp, next) {
    return group.findById(req, resp, next); 
}
