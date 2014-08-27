var Type = require('../type'),
    Resource = require('../resource'),
    common = require('../common');

var spec = {
    tableName : 'fos_user',
    primary_key : 'id',
    schema : {
        "id" : { type : Type.Int, props : ["default", "read_only"]},
        "username" : { type : Type.Str, props : ["default","required"]},
        "email" : { type : Type.Str, props : ["default","required"]},

        "username_canonical" : { type : Type.Str, props : ["required"]},
        "email_canonical" : { type : Type.Str, props : ["required"]},

        "country" : { type : Type.Str, props : []},
        "created" : { type : Type.Date, props : ["read_only"]},
        "updated" : { type : Type.Date, props : ["read_only"]},
        "about_me" : { type : Type.Str, props : []},
        "name" : { type : Type.Str, props : ["default"]},
        "organization" : { type : Type.Str, props : []},
        "title" : { type : Type.Str, props : []},
        "industry" : { type : Type.Str, props : []},
        "linkedIn" : { type : Type.Str, props : []},
        "professionalEmail" : { type : Type.Str, props : []},
        "twitterUsername" : { type : Type.Str, props : []},
        "website" : { type : Type.Str, props : []},
        "mailingAddress" : { type : Type.Str, props : []},

        "enabled" : { type : Type.Bool, props : [], initial : false },
        "salt" : { type : Type.Str, props : [] },
        "password" : { type : Type.Str, props : ["required"] },
        //"confirmation_token" : { type : Type.Str, props : ["default"]},
        //"password_requested_at" : { type : Type.Date, props : ["default"]},
        //"roles" : { type : Type.Str, props : ["default"]},
        //"ipAddress" : { type : Type.Str, props : [, "read_only"]},
        //"uuid" : { type : Type.Str, props : [, "read_only"]},
        //"gallary_id" : { type : Type.Int, props : []},
        //"faceprintId" : { type : Type.Int, props : [, "read_only"]},
        //"faceprint_image" : { type : Type.Int, props : [, "read_only"]},

        //enabled, salt, password, locked, expired, roles, credentials_expired, created, updated, facebook_id, twitter_id, about_me, faceprint_image, displayProfile, displayPrivateInfoToOrganizers
    }
};

var resource = new Resource(spec);
Type.User.init(resource);

exports.find_all = function (req, resp, next) {
    return resource.find_all(req, resp, next);
};

exports.find_by_primary_key = function (req, resp, next) {
    return resource.find_by_primary_key(req, resp, next);
};

exports.create = function (req, resp, next) {
    //use random salt regardless of value
    req.body.salt = common.make_salt();

    //hash provided password (required)
    if(req.body.password) {
        req.body.password = common.hash_password(req.body.password, req.body.salt)
    }

    return resource.create(req, resp, next);
};

exports.delete_by_primary_key = function (req, resp, next) {
    return resource.delete_by_primary_key(req, resp, next);
};