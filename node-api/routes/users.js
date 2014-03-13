var Type      = require('../type'),
    Resource  = require('../resource');
    

var schema = {
    "id":                   { type: Type.Int, props: ["default", "filterable", "read_only"]},
    "username":             { type: Type.Str, props: ["default", "filterable"]},
    "email":                { type: Type.Str, props: ["default", "filterable"]},
    "country":              { type: Type.Str, props: ["filterable"]},
    "created":              { type: Type.Date, props:["filterable", "read_only"]},
    "updated":              { type: Type.Date, props:["filterable", "read_only"]},
    "about_me":             { type: Type.Str, props: ["filterable"]},
    "name":                 { type: Type.Str, props: ["default", "filterable"]},
    "organization":         { type: Type.Str, props: ["filterable"]},
    "title":                { type: Type.Str, props: ["filterable"]},
    "industry":             { type: Type.Str, props: ["filterable"]},
    "linkedIn":             { type: Type.Str, props: ["filterable"]},
    "professionalEmail":    { type: Type.Str, props: ["filterable"]},
    "twitterUsername":      { type: Type.Str, props: ["filterable"]},
    "website":              { type: Type.Str, props: ["filterable"]},
    "mailingAddress":       { type: Type.Str, props: ["filterable"]},
    //"enabled":              { type: Type.Bool, props:["filterable", "read_only"]},
    //"salt":                 { type: Type.Str, props: ["default", "filterable"]},
    //"password":             { type: Type.Str, props: ["default", "filterable"]},
    //"confirmation_token":   { type: Type.Str, props: ["default", "filterable"]},
    //"password_requested_at": { type:Type.Date,props: ["default", "filterable"]},
    //"roles":                { type: Type.Str, props: ["default", "filterable"]},
    //"ipAddress":            { type: Type.Str, props: ["filterable", "read_only"]},
    //"uuid":                 { type: Type.Str, props: ["filterable", "read_only"]},
    //"gallary_id":           { type: Type.Int, props: ["filterable"]},
    //"faceprintId":          { type: Type.Int, props: ["filterable", "read_only"]},
    //"faceprint_image":      { type: Type.Int, props: ["filterable", "read_only"]},

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



