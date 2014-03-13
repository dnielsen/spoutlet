var restify = require('restify'),
    util    = require('./util'),
    common  = require('./common'),
    knex    = common.knex,
    Type    = require('./type');

//Schema properties:
// default : on GET this field will display unless 'filters' or 'verbose' queries are used
// read-only: this field can not be modified with a POST, PUT, or PATCH
// required: this field must be included in a PUT or POST
// filterable: this field can be searched for with a query parameter matching its name (or an alias)

//Constructor
var Resource = function (spec) {
    this.tableName =    spec.tableName;
    this.schema =       spec.schema;
    this.primary_key =  spec.primary_key;
    this.user_mapping = spec.user_mapping;
    this.deleted_col =  spec.deleted_col || false;
}
module.exports = Resource


//Define a constructor for Resource Types that is a special form of Type
var ResourceType = function() {}
Resource.ResourceType = ResourceType


//Attach the parent constructor as the prototype of this constructor
ResourceType.prototype = new Type;



//-------------------------------------------------------
//------------ Resource Type Declarations ---------------
//-------------------------------------------------------
Type.Group = new Resource.ResourceType();
Type.Group.Category = new Type();
Type.Event = new Resource.ResourceType();
Type.Session = new Resource.ResourceType();
Type.List = new Resource.ResourceType();
Type.List.Type = new Type();
Type.Registry = new Resource.ResourceType();
Type.Entry = new Resource.ResourceType();
Type.User = new Resource.ResourceType();
Type.Vote = new Resource.ResourceType();

//--------------------------------------------------------



ResourceType.prototype.init = function(resource, val, def_op, prefix_ops) {
    var validator =         val         || Type.Int.validate;
    var default_operator =  def_op      || Type.Int.default_filter;
    var prefix_operators =  prefix_ops  || Type.Int.prefix_filters;

    //Call the parent initializer 
    Type.prototype.init.call(this, validator, default_operator, prefix_operators);

    this.resource = resource;
}

//-------------------------------------------------

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
    var all_fields = Object.keys(this.schema);
    for(var i in all_fields) {
        var field           = all_fields[i];
        var field_def       = this.schema[field];

        var is_filterable   = field_def.hasOwnProperty("props") && field_def.props.indexOf("filterable") !== -1;
        
        var label           = field_def.hasOwnProperty("alias") ? field_def.alias : field;
        var was_provided    = req.query.hasOwnProperty(label);
        if(!is_filterable || !was_provided)
            continue;
        
        var value           = req.query[label];
        var field_type      = field_def["type"];

        field_type.apply_filter(this.tableName + '.' + field, query, value);
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

Resource.prototype.assemble_insert = function(post_data) {
    var insert_data = {};

    var all_fields = Object.keys(this.schema);
    for(var i in all_fields) {
        var field           = all_fields[i];
        var field_def       = this.schema[field];
        var was_posted      = post_data.hasOwnProperty(field);
        
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
        
        var fails_validation = !field_def.type.validate(value)
        if(fails_validation)
            throw new restify.InvalidArgumentError("'" + value + "' invalid for " + field);

        insert_data[field] = value;
    }
    
    return insert_data;
}

Resource.prototype.assemble_paging_links = function(req, max) {
    var that = this;

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

    var get_paging_link = function(limit, offset) {
        var queries = JSON.parse(JSON.stringify(req.query));
        queries.limit = limit

        if(offset == 0) 
            delete queries.offset;
        else 
            queries.offset = offset;

        return that.assemble_url(path, queries);
    };

    var return_value = {
        first: get_paging_link(limit,0),
        last: get_paging_link(limit, total_pages*limit),
        next: get_paging_link(limit, (current_page < total_pages ? current_page + 1 : current_page) * limit),
        prev: get_paging_link(limit, (current_page > 0 ? current_page - 1 : current_page) * limit),
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

//Where this resource is owned by me
Resource.prototype.where_is_mine = function(req, query) {
    if( !this.hasOwnProperty("user_mapping") )
        return;

    var user_mapping = this["user_mapping"];
    
    var user_id = req.user[user_mapping[0]];
    var resource_field = user_mapping[1];
    query.where(resource_field, user_id);
}

Resource.prototype.apply_envelopes = function(expansions, result_set) {
    
    //if original field name endeds with '_id' then remove it
    //otherwise use <original name>_details
    var get_envelope_name = function(temp_name) {
        if(temp_name.indexOf('expand_') === -1) {
            console.log("can't understand temp attribut name: "+temp_name);
            return;
        }

        var expand_index_str = key.substring('expand_'.length);
        var expand_index = parseInt(expand_index_str);
        var original_field_name = expansions[expand_index];
        
        //strip off '_id' if present
        if(/_id$/.test(original_field_name))
            return original_field_name.replace(/(.+)_id$/,'$1');
        else
            return original_field_name+'_details';
    }

    //processed elements list
    var new_result_set = {};
    var keys = Object.keys(result_set);
    for(var i in keys) {
        var key = keys[i];
        var split_key = key.split(':',2);
    
        //is envelope processing is not necessary?
        if(split_key.length == 1) {
            new_result_set[key] = result_set[key];
            continue;
        }

        //save the parts of the composit name seperatly 
        var envelope_name = get_envelope_name(split_key[0]);
        var prop_name = split_key[1];

        //add element to envelope, creating it if necessary 
        var envelope = new_result_set[envelope_name] || {};
        if(!new_result_set.hasOwnProperty(envelope_name)) {
            new_result_set[envelope_name] = envelope;
        } 
        envelope[prop_name] = result_set[key];
    }
    return new_result_set;
}

Resource.prototype.apply_expand = function(req, query) {
    if(!req.query.hasOwnProperty("expand"))
        return;

    var expansions = req.query.expand.split(",");
    
    //Inspect each field of the schema to determine what joins to add to the query
    var all_fields = Object.keys(this.schema);
    for(var i in all_fields) {
        var field = all_fields[i];
        var field_def = this.schema[field];

        var type = field_def.type;
    
        var is_resource_type = type instanceof ResourceType;
        if(!is_resource_type)
            continue;

        var request_index = expansions.indexOf(field);
        if( request_index < 0)
            continue;

        var resource = type.resource;
        var mapped_by = field_def.mappedBy;
        var alias = 'expand_' + request_index;

        //join the table renamed to expand_#
        query.join(resource.tableName + ' as ' + alias
            , this.tableName + '.' + field
            , '='
            , alias + '.' + mapped_by, "left");

        //select 'default' columns from joined table 
        var resource_keys = Object.keys(resource.schema);
        for(var j in resource_keys) {
            var expanded_field = resource_keys[j];
            var expanded_field_def = resource.schema[expanded_field];

            if(expanded_field_def.props.indexOf('default') < 0)
                continue;

            //name the new column 'expand_<#>:<field>'
            query.column( alias + '.' + expanded_field + ' as ' + alias + ':' + expanded_field);
        }
    }
}

// Basic resource type's allow for customization of the fields returned
// providing no query parameters (qp) returns just the default fields
// verbose qp will return all available fields,
// fields qp returns only the specified fields
// fields=name[,name] | verbose | (none)
Resource.prototype.processBasicQueryParams = function(req, query) {
    var fields = this.get_requested_fields(req,false);
    for(var i in fields)
        fields[i] = this.tableName + '.' + fields[i]; 

    this.apply_expand(req,query);
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
    
    var query = knex(this.tableName);
    if(this.deleted_col)
        query.where(this.tableName + '.' + this.deleted_col,0);
    
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

        var final_result_set = result_set;
        if(req.query.hasOwnProperty("expand")) {
            var expansions = req.query.expand.split(",");

            final_result_set = [];
            for(var i=0; i < result_set.length; i++) {
                final_result_set[i] = that.apply_envelopes(expansions, result_set[i]);
            }
        }

        resp.send(final_result_set); 
        next(); 
        return;
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
    var that = this;

    if(!this.primary_key)
        return next(new restify.InvalidArgumentError('no primary key for this resource'));

    var primary_key_field = this.primary_key;
    var primary_key = req.params[primary_key_field];
    
    var field_def = this.schema[primary_key_field];
    var fails_validation = !field_def.type.validate(primary_key)
    if(fails_validation)
        throw new restify.InvalidArgumentError("'" + primary_key + "' invalid for " + primary_key_field);

    var query = knex(this.tableName).where(this.tableName+'.'+primary_key_field, primary_key);
    if(this.deleted_col)
        query.andWhere(this.tableName+'.'+this.deleted_col,0);
    
    try {
        this.processBasicQueryParams(req, query);
    } catch(err) {
        return next(err); 
    }
    
    var send_response = function(result_set) {
        if (result_set === undefined || result_set.length == 0)
            return next(new restify.ResourceNotFoundError(primary_key));

        var final_result_set = result_set[0];
        if(req.query.hasOwnProperty("expand")) {
            var expansions = req.query.expand.split(",");
            final_result_set =  that.apply_envelopes(expansions, final_result_set);
        }

        resp.send(final_result_set);
        next();
    }
    
    query.then(send_response, function(err) {
        return next(new restify.RestError(err));
    });    
};

Resource.prototype.create = function(req, resp, next) {
    var that = this;
    
    //Process POST query parameters
    var expand_result = false;
    if(req.query.hasOwnProperty('expand'))
        expand_result = true;
    
    var insert_data;
    try {
        var has_user_mapping = this.hasOwnProperty("user_mapping");
        var my_field, user_field;
        if(has_user_mapping) {
            my_user_field = this.user_mapping[1];
            assoc_user_field = this.user_mapping[0];

            if(req.body.hasOwnProperty(my_user_field)  && req.body[my_user_field] != req.user[assoc_user_field]) {
                throw new restify.InvalidArgumentError('Cannot assign different user.');
            }
            req.body[my_user_field] = req.user[assoc_user_field];
        }

        insert_data = this.assemble_insert(req.body);
    } catch(e) { 
        return next(e);
    }

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
    
    var get_resource = function(resource_id) {
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
    
    var field_def = this.schema[primary_key_field];
    var fails_validation = !field_def.type.validate(primary_key)
    if(fails_validation)
        throw new restify.InvalidArgumentError("'" + primary_key + "' invalid for " + primary_key_field);
    
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
    
    this.where_is_mine(req, query);

    if(this.deleted_col) {
        var update = {};
        update[this.deleted_col] = 1;
        query.update(update).then(send_response, report_error);
    } else {
        query.del().then(send_response, report_error);
    }
}




