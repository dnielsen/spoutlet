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

var event = new Resource('pd_groups', defaultFields, allowedFields, 'deleted');

exports.findAll = function(req, resp, next) { 
    return event.findAll(req, resp, next); 
}
exports.findById = function(req, resp, next) {
    return event.findById(req, resp, next); 
}
