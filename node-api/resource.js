var restify = require('restify'),
    util    = require('./util'),
    common  = require('./common'),
    knex    = common.knex;

//Constructor
var Resource = function (spec) {
    this.tableName =    spec.tableName;
    
    this.schema = spec.schema;
    
    this.deleted_col =  spec.deleted_col || false;
    this.filters =      spec.filters || { 
        // label: [column_name [, operator]] 
        // allowed operators '=', '<', '>', '<=', '>=', 'like', 'not like', 'between', 'ilike'
        q: { field: 'name', operator: 'like' }
    };
}

//Assign constructor to module.exports
//Usage: 
//> var Resource = require(<this_file>);
//> var myRes = new Resource(<my_spec>);
module.exports = Resource

//-------------------------------------------------

Resource.prototype._get_fields_for_property = function(prop) {
    var matches = [];
    
    var schema = this.schema;
    for(var field in schema) {
        var info = schema[field];
        if( info.props && info.props.indexOf(prop) !== -1 )
            matches.push(field);
    }
    return matches;
}


// quiet (default false) determines if bad requests return exceptions
Resource.prototype.get_requested_fields = function(req, quiet) {
    var use_fields = req.query.hasOwnProperty('fields');
    var use_verbose = req.query.hasOwnProperty('verbose');
    
    if(!use_fields && !use_verbose)
        return this._get_fields_for_property("default");
    
    var all_keys = Object.keys(this.schema);
    if(use_verbose)
        return all_keys;
       
    var fields = [];
    var requested_fields = req.query.fields.split(',');
    for(var i in requested_fields) {
        var request = requested_fields[i];
        if(all_keys.indexOf(request) !== -1)
            fields.push(request);
        else if(!quiet)
            throw new restify.InvalidArgumentError(request);
    }
    return fields;
}

// Basic resource type's allow for customization of the fields returned
// providing no query parameters (qp) returns just the default fields
// verbose qp will return all available fields,
// fields qp returns only the specified fields
// fields=name[,name] | verbose | (none)
Resource.prototype.processBasicQueryParams = 
   function(req, query) {
        var fields = this.get_requested_fields(req,false);
        query.column(fields);
    }

Resource.prototype.processCollectionQueryParams = 
    function(req, query) {
    
        //inherit base query parameters
        this.processBasicQueryParams(req, query);
        
        if(req.query.hasOwnProperty('limit')) {
            var limit = req.query.limit;
            if(isNaN(limit))
                throw new restify.InvalidArgumentError(limit);
                
            query.limit(limit);
            
            if(req.query.hasOwnProperty('offset')) {
                var offset = req.query.offset;
                if(isNaN(offset))
                    throw new restify.InvalidArgumentError(offset);
                    
                query.offset(offset);
            }
        }
        
        // Format: sort_by=[-]<field>[,[-]<field>] including '-' will reverse sort the field
        // e.g. /groups?fields=id,category,featured&sort_by=-category,-featured
        if(req.query.hasOwnProperty('sort_by')) {
            var fields = req.query.sort_by.split(',');
            for(var i in fields) {
                var field = fields[i];
                
                var desc = false;
                if(field.indexOf('-') === 0) {
                    field = field.substr(1);
                    desc = true;
                }
                
                if(!this.schema.hasOwnProperty(field))
                    throw new restify.InvalidArgumentError("sort_by field '"+field+"' not recognized");
                
                query.orderBy(field, desc ? 'desc' : 'asc' );
            }
        }
        
        for(var label in this.filters) {
            if(req.query.hasOwnProperty(label)) {
                var field = this.filters[label].field;
                var op    = this.filters[label].operator || 'like';
                var value = req.query[label];
                if(op === 'like')
                    value = '%' + value + '%';
                    
                query.where(field, op, value);
            }
        }
        
    }


//--------------------------------------------------------


Resource.prototype.findAll = function(req, resp, next) {
    var that = this;
    
    var query = knex(this.tableName)
    if(this.deleted_col)
        query.where(this.deleted_col,0);
    
    try {
        this.processCollectionQueryParams(req, query);
    } catch(err) {
        return next(err); 
    }
    
    var result_set;
    
    var return_error = function(err) {
        return next(new restify.RestError(err));
    }
    
    var send_response = function(count_result) {
        var count = count_result[0]["count(*)"];
        
        resp.header('X-Length', result_set.length);
        resp.header('X-Total-Length', count);
            
        resp.send(result_set);
        next();
    }
    
    var ask_how_many = function(results) {
        result_set = results;
        
        var query = knex(that.tableName).count('*');
        if(that.deleted_col)
            query.where(that.deleted_col,0);
            
        query.then(send_response, return_error); 
    }
    
    query.then(ask_how_many, return_error);
    
};


Resource.prototype.findById = function(req, resp, next) {

    var id = req.params.id;
    if (isNaN(id)) {
        return next(new restify.InvalidArgumentError('id must be a number'));
    }
    
    var query = knex(this.tableName).where('id',id);
    if(this.deleted_col)
        query.andWhere(this.deleted_col,0);
    
    try {
        this.processBasicQueryParams(req, query);
    } catch(err) {
        return next(err); 
    }
    
    var send_response = function(results) {
        if (results === undefined || results.length == 0)
            return next(new restify.ResourceNotFoundError(id));
        resp.send(results[0]);
        next();
    }
    
    query.then(send_response, function(err) {
        return next(new restify.RestError(err));
    });
    
    
};

Resource.prototype.create = function(req, resp, next) {
    var that = this;
    
    var data_object = req.body;
    var fields = Object.keys(data_object);
    
    //must-contain validation
    var required_fields = that._get_fields_for_property('required');
    for(var rf_i in required_fields) {
        var rf = required_fields[rf_i];
        
        if(fields.indexOf(rf) === -1) {
            return next(new restify.MissingParameterError('must provide '+rf));
        }
    }
    
    //must-not-contain validation
    var banned_fields = that._get_fields_for_property('read_only');
    for(var f_i in fields) {
        var f = fields[f_i];
        
        if(banned_fields.indexOf(f) > -1) {
            return next(new restify.NotAuthorizedError('cannot accept '+f));
        }
        
        //TODO: parameter validation (important: scrub text types)
        
    }
    
    //Process POST query parameters
    var expand_result = false;
    if(req.query.hasOwnProperty('expand'))
        expand_result = true;
    
    var resource_id;
    
    var return_error = function(err) {
        console.error(err.message);
        var response = "Internal Error";
        
        var orig_message = err.message;
        if(orig_message.indexOf("ER_NO_REFERENCED_ROW_:") !== -1)
            response = "Referenced object could not be found";
        else if(orig_message.indexOf("ER_DUP_ENTRY") !== -1) 
            response = "Duplicate entry";
        else if(orig_message.indexOf("ER_BAD_FIELD_ERROR:") !== -1) 
            response = "A field was not recognized";
            
        resp.send(new restify.RestError(new Error(response)));
        next();
    }
    
    var send_response = function(result) {
        resp.header('Link', common.baseUrl + req.path() + "/" + resource_id);
        
        if(typeof result === 'object' )
            resp.send(201, result[0]);
        else
            resp.send(201, result);
            
        return next();
    }
    
    var get_resource = function(id) {
        resource_id = id;
    
        if(!expand_result) 
            return send_response();
        
        var default_fields = that._get_fields_for_property('default');
        knex(that.tableName).select(default_fields).where('id',id).
          then(send_response,return_error);
    }
    
    //Assemble 
    var sql_expr = knex(this.tableName)
        .insert(data_object)
        .then(get_resource, return_error);
}






