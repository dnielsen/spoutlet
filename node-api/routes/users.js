var Type      = require('../type'),
    Resource  = require('../resource');
    

var schema = {
    "id":                   { type: Type.Int, props: ["default", "read_only"]},
    "username":             { type: Type.Str, props: ["default"]},
    "email":                { type: Type.Str, props: ["default"]},
    "country":              { type: Type.Str, props: []},
    "created":              { type: Type.Date, props:[, "read_only"]},
    "updated":              { type: Type.Date, props:[, "read_only"]},
    "about_me":             { type: Type.Str, props: []},
    "name":                 { type: Type.Str, props: ["default"]},
    "organization":         { type: Type.Str, props: []},
    "title":                { type: Type.Str, props: []},
    "industry":             { type: Type.Str, props: []},
    "linkedIn":             { type: Type.Str, props: []},
    "professionalEmail":    { type: Type.Str, props: []},
    "twitterUsername":      { type: Type.Str, props: []},
    "website":              { type: Type.Str, props: []},
    "mailingAddress":       { type: Type.Str, props: []},
    //"enabled":              { type: Type.Bool, props:[, "read_only"]},
    //"salt":                 { type: Type.Str, props: ["default"]},
    //"password":             { type: Type.Str, props: ["default"]},
    //"confirmation_token":   { type: Type.Str, props: ["default"]},
    //"password_requested_at": { type:Type.Date,props: ["default"]},
    //"roles":                { type: Type.Str, props: ["default"]},
    //"ipAddress":            { type: Type.Str, props: [, "read_only"]},
    //"uuid":                 { type: Type.Str, props: [, "read_only"]},
    //"gallary_id":           { type: Type.Int, props: []},
    //"faceprintId":          { type: Type.Int, props: [, "read_only"]},
    //"faceprint_image":      { type: Type.Int, props: [, "read_only"]},

}; 
var resource = new Resource( {
    tableName: 'fos_user',
    primary_key:'id',
    schema: schema
} );
Type.User.init(resource);

    
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



