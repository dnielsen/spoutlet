var restify = require('restify'),
    util    = require('./util'),
    common  = require('./common'),
    knex    = common.knex;

//Constructor
var Resource = function (spec) {
    this.tableName =    spec.tableName;
    this.schema =       spec.schema;
    this.primary_key =  spec.primary_key;
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

Resource.prototype.apply_filters = function(req, query) {
    for(var label in this.filters) {
        if(!req.query.hasOwnProperty(label))
            continue;

        var field = this.filters[label].field;
        var op    = this.filters[label].operator || 'like';
        var value = req.query[label];
        
        var field_def       = this.schema[field];
        var fails_validation = field_def.hasOwnProperty('validator') && !field_def.validator(value)
        if(fails_validation)
            throw new restify.InvalidArgumentError("'" + value + "' invalid for " + field);

        if(op === 'like')
            value = '%' + value + '%';
            
        query.where(field, op, value);
    }
}


// Format: sort_by=[-]<field>[,[-]<field>] including '-' will reverse sort the field
// e.g. /groups?fields=id,category,featured&sort_by=-category,-featured
// On quiet refrain from throwing exceptions for invalid fields
Resource.prototype.apply_sorting = function(req, query, quiet) {
    if(!req.query.hasOwnProperty('sort_by'))
        return;

    var fields = req.query.sort_by.split(',');
    for(var i in fields) {
        var field = fields[i];
        
        var desc = false;
        if(field.indexOf('-') === 0) {
            field = field.substr(1);
            desc = true;
        }
        
        if(this.schema.hasOwnProperty(field)) {
            query.orderBy(field, desc ? 'desc' : 'asc' );
        } else if(!quiet)
            throw new restify.InvalidArgumentError("sort_by field '"+field+"' not recognized");
    }
}

Resource.prototype.assemble_url = function(path, queries) {
    var query_string = "";
    var is_first = true;
    for(var query in queries) {
        var value = queries[query];
        if(is_first) {
            is_first = false;
            query_string += "?" + query + "=" + value;
        } else {
            query_string += "&" + query + "=" + value;
        }
    }
    return common.baseUrl + path + query_string;
}

Resource.prototype.assemble_paging_links = function(req, max) {
    var limit = req.query.limit;
    var offset = req.query.offset;
    if(max === '' || isNaN(max) || limit === '' || isNaN(limit))
        return;

    //sanatize limit and offset
    if( limit > max) limit = max;
    if( limit < 0) limit = 1;
    if(offset === '' || isNaN(offset) || offset < 0) offset = 0;
    if(offset > max) offset = max


    var current_page = Math.floor( offset / limit );
    var total_pages = Math.floor( max / limit );
    if(max % limit == 0)
        total_pages = total_pages - 1;

    var path = req.path();

    var first_queries = JSON.parse(JSON.stringify(req.query));
    first_queries.limit = limit
    first_queries.offset  = 0;

    var last_queries = JSON.parse(JSON.stringify(req.query));
    last_queries.limit = limit;
    last_queries.offset = total_pages * limit; //remainder > 0 ? max - remainder : max - limit; 


    var next_queries = JSON.parse(JSON.stringify(req.query));
    next_queries.limit = limit;
    next_queries.offset = (current_page < total_pages ? current_page + 1 : current_page) * limit;

    var prev_queries = JSON.parse(JSON.stringify(req.query));
    prev_queries.limit = limit;
    prev_queries.offset = (current_page > 0 ? current_page - 1 : current_page) * limit;

    var return_value = {
        first: this.assemble_url(path, first_queries),
        last: this.assemble_url(path, last_queries),
        next: this.assemble_url(path, next_queries),
        prev: this.assemble_url(path, prev_queries),
    };
    return return_value;
}

//Allows user agent to request a view of the total data by size and starting count.
//Format: limit=<size of result set>[,offset=<num to skip over>]
Resource.prototype.apply_paging = function(req, query, quiet) {
    if(!req.query.hasOwnProperty('limit'))
        return;

    var limit = req.query.limit;
    if(limit === '' || isNaN(limit) || limit < 0) {
        if(quiet) return;
        throw new restify.InvalidArgumentError("limit value " + limit);
    }
    query.limit(limit);
    
    if(!req.query.hasOwnProperty('offset'))
        return;

    var offset = req.query.offset;
    if(offset === '' || isNaN(offset)) {
        if(quiet) return;
        throw new restify.InvalidArgumentError("offset value " + offset);
    }
    query.offset(offset);
}

// Basic resource type's allow for customization of the fields returned
// providing no query parameters (qp) returns just the default fields
// verbose qp will return all available fields,
// fields qp returns only the specified fields
// fields=name[,name] | verbose | (none)
Resource.prototype.processBasicQueryParams = function(req, query) {
    var fields = this.get_requested_fields(req,false);
    query.column(fields);
}

Resource.prototype.processCollectionQueryParams = function(req, query) {
    this.processBasicQueryParams(req, query);
    this.apply_paging(req, query, false);
    this.apply_sorting(req, query, false);
    this.apply_filters(req, query);
}


//--------------------------------------------------------


Resource.prototype.find_all = function(req, resp, next) {
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
        
        var paging_links = that.assemble_paging_links(req, count);
        if(paging_links) {
            resp.header('X-First', paging_links.first);
            resp.header('X-Last', paging_links.last);
            resp.header('X-Next', paging_links.next);
            resp.header('X-Prev', paging_links.prev);
        }

        resp.send(result_set);
        next();
    }
    
    var ask_how_many = function(results) {
        result_set = results;
        
        var query = knex(that.tableName).count('*');
        if(that.deleted_col)
            query.where(that.deleted_col,0);
        
        that.apply_filters(req, query);
        query.then(send_response, return_error); 
    }
    
    query.then(ask_how_many, return_error);
};

Resource.prototype.find_by_primary_key = function(req, resp, next) {
    if(!this.primary_key)
        return next(new restify.InvalidArgumentError('no primary key for this resource'));

    var primary_key_field = this.primary_key;
    var primary_key = req.params[primary_key_field];
    
    //TODO: validate by type
    var key_type = this.schema[primary_key_field].type;
    if(key_type == 'int' && isNaN(primary_key)) {
        return next(new restify.InvalidArgumentError('identifier is not a number'));
    } else if(key_type == 'string' && primary_key === "") {
        return next(new restify.InvalidArgumentError('identifier is empty string'));
    }

    var query = knex(this.tableName).where(primary_key_field, primary_key);
    if(this.deleted_col)
        query.andWhere(this.deleted_col,0);
    
    try {
        this.processBasicQueryParams(req, query);
    } catch(err) {
        return next(err); 
    }
    
    var send_response = function(results) {
        if (results === undefined || results.length == 0)
            return next(new restify.ResourceNotFoundError(primary_key));
        resp.send(results.length === 1 ? results[0] : results);
        next();
    }
    
    query.then(send_response, function(err) {
        return next(new restify.RestError(err));
    });    
};

Resource.prototype.assemble_insert = function(post_data) {
    var insert_data = {};

    var all_fields = Object.keys(this.schema);
    for(var i in all_fields) {
        var field           = all_fields[i];
        var was_posted      = post_data.hasOwnProperty(field);
        var field_def       = this.schema[field];

        var is_required     = field_def.hasOwnProperty("props") && field_def.props.indexOf("required") !== -1;
        if(is_required && !was_posted )
            throw new restify.MissingParameterError('must provide ' + field);

        var is_read_only    = field_def.hasOwnProperty("props") && field_def.props.indexOf("read-only") !== -1;
        if(is_read_only && was_posted)
            throw new restify.InvalidArgumentError('cannot set field ' + field);

        var has_default     = field_def.hasOwnProperty('initial');
        if(!was_posted && !has_default)
            continue;

        var value           = was_posted ? post_data[field] : 
            ( typeof field_def.initial === "function" ? field_def.initial() : field_def.initial);
        
        var fails_validation = field_def.hasOwnProperty('validator') && !field_def.validator(value)
        if(fails_validation)
            throw new restify.InvalidArgumentError("'" + value + "' invalid for " + field);

        insert_data[field] = value;
    }
    
    return insert_data;
}

Resource.prototype.create = function(req, resp, next) {
    var that = this;
    
    //Process POST query parameters
    var expand_result = false;
    if(req.query.hasOwnProperty('expand'))
        expand_result = true;
    
    var insert_data;
    try {
        insert_data = this.assemble_insert(req.body);
    } catch(e) { return next(e); }

    var return_error = function(err) {
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
        if(typeof result === 'object' ) 
            resp.send(201, result[0]);
        else 
            resp.send(201, result);
        return next();
    }
    
    var get_resource = function() {
        console.log("response from 'create': "+resource_id);
        
        resp.header('Link', common.baseUrl + req.path() + "/" + resource_id);
        
        if(!expand_result) 
            return send_response();
        
        var default_fields = that._get_fields_for_property('default');

        knex(that.tableName)
        .select(default_fields)
        .where(insert_data)
        .then(send_response,return_error);
    }

    //Assemble 
    var sql_expr = knex(this.tableName)
        .insert(insert_data)
        .then(get_resource, return_error);
}

Resource.prototype.delete_by_primary_key = function(req, resp, next) {
    if(!this.primary_key)
        return next(new restify.InvalidArgumentError('no primary key for this resource'));

    var primary_key_field = this.primary_key;
    var primary_key = req.params[primary_key_field];
    
    //TODO: validate by type
    var key_type = this.schema[primary_key_field].type;
    if(key_type == 'int' && isNaN(primary_key)) {
        return next(new restify.InvalidArgumentError('identifier is not a number'));
    } else if(key_type == 'string' && primary_key === "") {
        return next(new restify.InvalidArgumentError('identifier is empty string'));
    }
    
    var send_response = function(results) {
        if (results === undefined || results === 0 || results.length == 0)
            return next(new restify.ResourceNotFoundError(primary_key));
        
        resp.send( 200, "Deleted: " + (results.length === 1 ? results[0] : results) );
        next();
    }

    var report_error = function(err) {
        return next(new restify.RestError(err));
    }

    var query = knex(this.tableName).where(primary_key_field, primary_key);
    if(this.deleted_col) {
        var update = {};
        update[this.deleted_col] = 1;
        query.update(update).then(send_response, report_error);
    } else {
        query.del().then(send_response, report_error);
    }
}




