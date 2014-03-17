var Type = require('../type'),
    Resource = require('../resource');

var spec = {
    tableName : 'group_event',
    primary_key : 'id',
    user_mapping : { user : 'id', me : 'user_id' },
    deleted_col : 'deleted',
    schema : {
        "id" : { type : Type.Int, props : ["default", "read-only"] },
        "attendeeCount" : { type : Type.Int, props : ["default"] },
        "private" : { type : Type.Bool, props : ["no-filter"] },
        "name" : { type : Type.Str, props : ["required", "default"] },
        "slug" : { type : Type.Str, props : ["default"] },
        "content" : { type : Type.Str, props : ["default", "required"] },
        "registration_option" : { type : Type.Str, props : ["no-filter"] },
        "online" : { type : Type.Bool, props : [] },
        "starts_at" : { type : Type.Date, props : ["default"] },
        "ends_at" : { type : Type.Date, props : ["default"] },
        "external_url" : { type : Type.Str, props : ["no-filter"] },
        "location" : { type : Type.Str, props : [] },
        "address1" : { type : Type.Str, props : ["default"] },
        "address2" : { type : Type.Str, props : ["default"] },
        "latitude" : { type : Type.Str, props : [] },
        "longitude" : { type : Type.Str, props : [] },
        "created_at" : { type : Type.Date, props : ["read-only"] },
        "updated_at" : { type : Type.Date, props : ["read-only"] },
        "currentRound" : { type : Type.Int, props : ["read-only", "no-filter"] },
        "group_id" : { type : Type.Int, props : ["default", "required"] },
        "user_id" : { type : Type.Int, props : ["read-only"] },
        "entrySetRegistration_id" : { type : Type.Int, props : [] },

        "group" : { type : Type.Group, rel : "belongs_to", mapping : 'group_id' },
        "user" : { type : Type.User, rel : "belongs_to", mapping : 'user_id' },
        "entrySetRegistration" : { type : Type.Registry, rel : "belongs_to", mapping : "entrySetRegistration_id" }
    }
};

var resource = new Resource(spec);
Type.Event.init(resource);


exports.find_all = function (req, resp, next) {
    return resource.find_all(req, resp, next);
};

exports.find_by_primary_key = function (req, resp, next) {
    return resource.find_by_primary_key(req, resp, next);
};

exports.create = function (req, resp, next) {
    return resource.create(req, resp, next);
};

exports.delete_by_primary_key = function (req, resp, next) {
    return resource.delete_by_primary_key(req, resp, next);
};