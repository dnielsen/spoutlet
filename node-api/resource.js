var restify = require('restify'),
    common = require('./common'),
    knex = common.knex,
    Type = require('./type'),
    __ = require("underscore");

//Schema properties : 
// default : on GET this field will display unless 'filters' or 'verbose' queries are used
// read-only : this field can not be modified with a POST, PUT, or PATCH
// required : this field must be included in a PUT or POST
// no-filter : this field can be searched for with a query parameter matching its name (or an alias)

//Constructor
var Resource = function (spec) {
    var that = this;

    this.tableName = spec.tableName;
    this.primary_key = spec.primary_key;
    this.user_mapping = spec.user_mapping;
    this.deleted_col = spec.deleted_col || false;
    this.schema = spec.schema;
    this.filters = [];

    var parse_relation = function (label, field_def) {
        var relationship = field_def.rel;
        if (relationship === undefined) {
            relationship = 'owns';
        }

        delete field_def.rel;
        if (that[relationship] === undefined) {
            that[relationship] = {};
        }
        that[relationship][label] = field_def;
    };

    var parse_props = function (label, field_def) {
        var props = field_def.props;

        if (props === undefined || !__.contains(props, 'no-filter')) {
            that.filters.push(label);
        }
    };

    /*jslint forin: true*/
    var key, field_def;
    for (key in spec.schema) {
        if (!spec.schema.hasOwnProperty(key)) { continue; }

        field_def = spec.schema[key];

        //split out field definitions by the type of relation
        parse_relation(key, field_def);

        //save a list of filterable fields
        parse_props(key, field_def);
    }

};
module.exports = Resource;


//Define a constructor for Resource Types that is a special form of Type
var ResourceType = function () { return; };
Resource.ResourceType = ResourceType;


//Attach the parent constructor as the prototype of this constructor
ResourceType.prototype = new Type();



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

ResourceType.prototype.init = function (resource, val, def_op, prefix_ops) {

    //treat as an integer
    var validator = val || Type.Int.validate;
    var default_operator = def_op || Type.Int.default_filter;
    var prefix_operators = prefix_ops || Type.Int.prefix_filters;

    //Call the parent initializer 
    Type.prototype.init.call(this, validator, default_operator, prefix_operators);

    this.resource = resource;
};


//------------------------- GET Functions ------------------------


// Basic resource type's allow for customization of the fields returned
// providing no query parameters (qp) returns just the default fields
// verbose qp will return all available fields,
// fields qp returns only the specified fields
// fields=name[,name] | verbose | (none)
Resource.prototype.processBasicQueryParams = function (req, query) {
    this.apply_columns(req, query);
};

Resource.prototype.processCollectionQueryParams = function (req, query) {
    this.processBasicQueryParams(req, query);
    this.apply_paging(req, query, false);
    this.apply_sorting(req.query, query, false);
    this.apply_filters(req.query, query);
};


Resource.prototype.find_by_primary_key = function (req, resp, next) {
    var that = this;

    if (!this.primary_key) {
        return next(new restify.InvalidArgumentError('no primary key for this resource'));
    }

    var primary_key_field = this.primary_key;
    var primary_key = req.params[primary_key_field];

    var field_def = this.owns[primary_key_field];
    var fails_validation = !field_def.type.validate(primary_key);
    if (fails_validation) {
        throw new restify.InvalidArgumentError("'" + primary_key + "' invalid for " + primary_key_field);
    }

    var query = knex(this.tableName).where(this.tableName + '.' + primary_key_field, primary_key);
    if (this.deleted_col) {
        query.andWhere(this.tableName + '.' + this.deleted_col, 0);
    }

    try {
        this.processBasicQueryParams(req, query);
    } catch (err) {
        return next(err);
    }

    var send_response = function (result_set) {
        if (result_set === undefined || result_set.length === 0) {
            return next(new restify.ResourceNotFoundError(primary_key));
        }

        var enveloped_result_set = that.apply_envelopes(result_set[0]);
        resp.send(enveloped_result_set);
        next();
    };

    query.then(send_response, function (err) {
        return next(new restify.RestError(err));
    });
};

Resource.prototype.find_all = function (req, resp, next) {
    var that = this;

    var query = knex(this.tableName);
    if (this.deleted_col) {
        query.where(this.tableName + '.' + this.deleted_col, 0);
    }

    try {
        this.processCollectionQueryParams(req, query);
    } catch (err) {
        return next(err);
    }

    var result_set;

    var return_error = function (err) {
        return next(new restify.RestError(err));
    };

    var send_response = function (count_result) {
        var count = count_result[0]["count(*)"];

        resp.header('X-Length', result_set.length);
        resp.header('X-Total-Length', count);

        var paging_links = that.assemble_paging_links(req, count);
        if (paging_links) {
            resp.header('X-First', paging_links.first);
            resp.header('X-Last', paging_links.last);
            resp.header('X-Next', paging_links.next);
            resp.header('X-Prev', paging_links.prev);
        }

        var enveloped_result_set = [];
        __.each(result_set, function (result) {
            enveloped_result_set.push(that.apply_envelopes(result));
        });

        resp.send(enveloped_result_set);
        next();
        return;
    };

    var ask_how_many = function (results) {
        result_set = results;

        var count_query = knex(that.tableName).count('*');
        if (that.deleted_col) {
            count_query.where(that.tableName + '.' + that.deleted_col, 0);
        }
        that.apply_filters(req, count_query);
        count_query.then(send_response, return_error);
    };

    query.then(ask_how_many, return_error);
};


Resource.prototype.apply_columns_helper = function (choose_stmts, query, defaults_only) {
    var that = this;

    //return all columns
    choose_stmts(this);

    __.each(this.belongs_to, function (belongs_to, belongs_to_label) {
        if (defaults_only && (!belongs_to.props || belongs_to.props.indexOf('default') === -1)) {
            return;
        }

        var resource = belongs_to.type.resource;
        var resource_used = choose_stmts(resource, belongs_to_label);
        if (!resource_used) {
            return;
        }

        //add the correct join statement to our query if columns were found
        that.join_table(resource, belongs_to_label, query);
    });
};


Resource.prototype.apply_columns = function (req, query) {
    var add_column = function (column_name, resource, label) {
        var prefix = (label || resource.tableName);
        query.column(prefix + '.' + column_name + " as " + prefix + '_' + column_name);
    };

    var get_all_column_stmts = function (resource, label) {
        __.each(resource.owns, function (column, column_name) {
            add_column(column_name, resource, label);
        });
        return true;
    };

    var get_default_column_stmts = function (resource, label) {
        var resource_used = false;
        __.each(resource.owns, function (column_def, column_name) {
            if (__.has(column_def, 'props') && __.contains(column_def.props, 'default')) {
                add_column(column_name, resource, label);
                resource_used = true;
            }
        });
        return resource_used;
    };

    var requested_columns = req.query.fields ? req.query.fields.split(',') : undefined;
    var get_requested_column_stmts = function (resource, label) {
        var resource_used = false;

        __.each(requested_columns, function (requested_col) {
            //require the label match if provided
            if (label) {
                if (requested_col.substring(0, label.length) !== label) { return; }
                requested_col = requested_col.substring(label.length + 1);
            }

            //validate the request
            if (__.has(resource.owns, requested_col)) {
                add_column(requested_col, resource, label);
                resource_used = true;
            }
        });
        return resource_used;
    };

    if (req.query.hasOwnProperty('verbose')) {
        this.apply_columns_helper(get_all_column_stmts, query);
    } else if (!req.query.hasOwnProperty('fields')) {
        this.apply_columns_helper(get_default_column_stmts, query, true);
    } else {
        this.apply_columns_helper(get_requested_column_stmts, query);
    }
};


Resource.prototype.apply_envelopes = function (result_set) {
    var that = this;
    var envelope_names = [this.tableName];
    if (__.has(this, 'belongs_to')) {
        envelope_names = envelope_names.concat(__.keys(this.belongs_to));
    }
    if (__.has(this, 'has_many')) {
        envelope_names = envelope_names.concat(__.keys(this.has_many));
    }

    var enveloped_result_set = {};
    __.each(result_set, function (value, label) {
        __.find(envelope_names, function (envelope_name) {
            //if no match continue
            if (label.substring(0, envelope_name.length) !== envelope_name) {
                return false;
            }

            //trim off the envelope name
            var short_label = label.substring(envelope_name.length + 1);

            //insert value into the envelope
            //special handling for envelope (this.tableName), it's our local values so no envelope
            if (envelope_name === that.tableName) {
                enveloped_result_set[short_label] = value;
            } else {
                //create the envelope if it isn't already there
                if (!__.has(enveloped_result_set, envelope_name)) {
                    enveloped_result_set[envelope_name] = {};
                }
                enveloped_result_set[envelope_name][short_label] = value;
            }
            return true;
        });
    });

    return enveloped_result_set;
};


Resource.prototype.apply_filters = function (requests, query, label) {
    var that = this;
    var filters = this.filters;
    __.each(filters, function (filter_name) {
        //if not requested, continue looking
        if (!requests.hasOwnProperty(filter_name)) { return; }

        var type = that.schema[filter_name].type;

        //handle direct relation first
        if (!(type instanceof ResourceType)) {
            type.apply_filter((label || that.tableName) + '.' + filter_name, query, requests[filter_name]);
            return;
        }

        //label is the handle for an associated resource from a previous call
        if (label !== undefined) {
            throw new restify.InvalidArgumentError("filters may only be nested to 1 level");
        }

        var value = requests[filter_name];

        /*jslint regexp: true*/
        //strip quotes, validation done by apply_filter
        value = value.replace(/['|"]([^'"]*)['|"]/, '$1');

        //parse nested qp's
        var subrequests = {};
        __.each(value.split(','), function (kv) {
            var split = kv.split('=');
            subrequests[split.shift()] = split.join('=');
        });

        //call apply_filters on the type's resource
        type.resource.apply_filters(subrequests, query, filter_name);
        that.join_table(type.resource, filter_name, query);
    });
};


// Format : sort_by=[-]<field>[,[-]<field>] including '-' will reverse sort the field
// e.g. /groups?fields=id,category,featured&sort_by=-category,-featured
// On quiet refrain from throwing exceptions for invalid fields
Resource.prototype.apply_sorting = function (requests, query, quiet) {
    var that = this;
    if (!requests.hasOwnProperty('sort_by')) {
        return;
    }

    var fields = requests.sort_by.split(',');
    __.each(fields, function (field) {
        var orentation = 'asc';
        if (field.indexOf('-') === 0) {
            field = field.substr(1);
            orentation = 'desc';
        }

        if (that.owns.hasOwnProperty(field)) {
            query.orderBy(that.tableName + '.' + field, orentation);
            return;
        }

        //allow for subfield sorting
        var parts = field.split('.');
        if (parts.length === 2) {
            var label = parts[0];
            var subfield = parts[1];
            if (that.belongs_to && that.belongs_to[label]) {
                var type = that.belongs_to[label].type;
                if (type.resource.owns.hasOwnProperty(subfield)) {
                    query.orderBy(label + '.' + subfield, orentation);
                    that.join_table(type.resource, label, query);
                    return;
                }
            }
        }

        if (quiet) { return; }
        throw new restify.InvalidArgumentError("sort_by field '" + field + "' not recognized");
    });
};

Resource.prototype.assemble_paging_links = function (req, max) {
    var that = this;

    var limit = req.query.limit;
    var offset = req.query.offset;
    if (max === '' || isNaN(max) || limit === '' || isNaN(limit))
        return;

    //sanatize limit and offset
    if (limit > max) limit = max;
    if (limit < 0) limit = 1;
    if (offset === '' || isNaN(offset) || offset < 0) offset = 0;
    if (offset > max) offset = max;

    var current_page = Math.floor(offset / limit);
    var total_pages = Math.floor(max / limit);
    if (max % limit === 0) {
        total_pages = total_pages - 1;
    }

    var path = req.path();

    var get_paging_link = function (limit, offset) {
        var queries = JSON.parse(JSON.stringify(req.query));
        queries.limit = limit;

        if (offset === 0) {
            delete queries.offset;
        } else {
            queries.offset = offset;
        }
        return that.assemble_url(path, queries);
    };

    var return_value = {
        first : get_paging_link(limit, 0),
        last : get_paging_link(limit, total_pages * limit),
        next : get_paging_link(limit, (current_page < total_pages ? current_page + 1 : current_page) * limit),
        prev : get_paging_link(limit, (current_page > 0 ? current_page - 1 : current_page) * limit),
    };
    return return_value;
};

//Allows user agent to request a view of the total data by size and starting count.
//Format : limit=<size of result set>[,offset=<num to skip over>]
Resource.prototype.apply_paging = function (req, query, quiet) {
    if (!req.query.hasOwnProperty('limit'))
        return;

    var limit = req.query.limit;
    if (limit === '' || isNaN(limit) || limit < 0) {
        if (quiet) return;
        throw new restify.InvalidArgumentError("limit value " + limit);
    }
    query.limit(limit);

    if (!req.query.hasOwnProperty('offset'))
        return;

    var offset = req.query.offset;
    if (offset === '' || isNaN(offset)) {
        if (quiet) return;
        throw new restify.InvalidArgumentError("offset value " + offset);
    }
    query.offset(offset);
};


//-----------------------Create / Update / Delete---------------------------------


Resource.prototype.assemble_insert = function (post_data) {
    var insert_data = {};

    var all_fields = Object.keys(this.owns);
    var i;
    for (i in all_fields) {
        var field = all_fields[i];
        var field_def = this.owns[field];
        var was_posted = post_data.hasOwnProperty(field);

        var is_required = field_def.hasOwnProperty("props") && field_def.props.indexOf("required") !== -1;
        if (is_required && !was_posted)
            throw new restify.MissingParameterError('must provide ' + field);

        var is_read_only = field_def.hasOwnProperty("props") && field_def.props.indexOf("read-only") !== -1;
        if (is_read_only && was_posted)
            throw new restify.InvalidArgumentError('cannot set field ' + field);

        var has_default = field_def.hasOwnProperty('initial');
        if (!was_posted && !has_default)
            continue;

        var value = was_posted ? post_data[field] : (typeof field_def.initial === "function" ? field_def.initial() : field_def.initial);

        var fails_validation = !field_def.type.validate(value);
        if (fails_validation)
            throw new restify.InvalidArgumentError("'" + value + "' invalid for " + field);

        insert_data[field] = value;
    }

    return insert_data;
};

Resource.prototype.create = function (req, resp, next) {
    var that = this;

    //Process POST query parameters
    var expand_result = false;
    if (req.query.hasOwnProperty('expand'))
        expand_result = true;

    var insert_data;
    try {
        var has_user_mapping = this.hasOwnProperty("user_mapping");
        var my_field, user_field;
        if (has_user_mapping) {
            my_user_field = this.user_mapping[1];
            assoc_user_field = this.user_mapping[0];

            if (req.body.hasOwnProperty(my_user_field) && req.body[my_user_field] != req.user[assoc_user_field]) {
                throw new restify.InvalidArgumentError('Cannot assign different user.');
            }
            req.body[my_user_field] = req.user[assoc_user_field];
        }

        insert_data = this.assemble_insert(req.body);
    } catch (e) {
        return next(e);
    }

    var return_error = function (err) {
        var response = "Internal Error";

        var orig_message = err.message;
        if (orig_message.indexOf("ER_NO_REFERENCED_ROW_ : ") !== -1)
            response = "Referenced object could not be found";
        else if (orig_message.indexOf("ER_DUP_ENTRY") !== -1)
            response = "Duplicate entry";
        else if (orig_message.indexOf("ER_BAD_FIELD_ERROR : ") !== -1)
            response = "A field was not recognized";

        resp.send(new restify.RestError(new Error(response)));
        next();
    };

    var send_response = function (result) {
        if (typeof result === 'object')
            resp.send(201, result[0]);
        else
            resp.send(201, result);
        return next();
    };

    var get_resource = function (resource_id) {
        resp.header('Link', common.baseUrl + req.path() + "/" + resource_id);

        if (!expand_result)
            return send_response();

        var default_fields = that._get_fields_for_property('default');

        knex(that.tableName)
            .select(default_fields)
            .where(insert_data)
            .then(send_response, return_error);
    };

    //Assemble 
    var sql_expr = knex(this.tableName)
        .insert(insert_data)
        .then(get_resource, return_error);
};

Resource.prototype.delete_by_primary_key = function (req, resp, next) {
    if (!this.primary_key)
        return next(new restify.InvalidArgumentError('no primary key for this resource'));

    var primary_key_field = this.primary_key;
    var primary_key = req.params[primary_key_field];

    var field_def = this.owns[primary_key_field];
    var fails_validation = !field_def.type.validate(primary_key);
    if (fails_validation)
        throw new restify.InvalidArgumentError("'" + primary_key + "' invalid for " + primary_key_field);

    var send_response = function (results) {
        if (results === undefined || results === 0 || results.length === 0)
            return next(new restify.ResourceNotFoundError(primary_key));

        resp.send(200, "Deleted : " + (results.length === 1 ? results[0] : results));
        next();
    };

    var report_error = function (err) {
        return next(new restify.RestError(err));
    };

    var query = knex(this.tableName).where(primary_key_field, primary_key);

    this.where_is_mine(req, query);

    if (this.deleted_col) {
        var update = {};
        update[this.deleted_col] = 1;
        query.update(update).then(send_response, report_error);
    } else {
        query.del().then(send_response, report_error);
    }
};


//------------------ Utility Functions ----------------


Resource.prototype.assemble_url = function (path, queries) {
    var query_string = "";
    var is_first = true;

    /*jslint forin: true*/
    var query, value;
    for (query in queries) {
        if (!queries.hasOwnProperty(query)) { continue; }

        value = queries[query];
        if (is_first) {
            is_first = false;
            query_string += "?" + query + "=" + value;
        } else {
            query_string += "&" + query + "=" + value;
        }
    }
    return common.baseUrl + path + query_string;
};

Resource.prototype.join_table = function (resource, label, query) {
    if (__.contains(query.joined, label))
        return;

    var table_name = resource.tableName + " as " + label;
    var lhs = this.tableName + "." + this.belongs_to[label].mapping;
    var rhs = label + '.' + resource.primary_key;
    query.join(table_name, lhs, '=', rhs, 'left'); // left join allows other tbls to be null.

    if (query.joined === undefined)
        query.joined = [];
    query.joined.push(label);
};

//Where this resource is owned by me
Resource.prototype.where_is_mine = function (req, query) {
    if (!this.hasOwnProperty("user_mapping"))
        return;

    var user_mapping = this["user_mapping"];

    var user_id = req.user[user_mapping[0]];
    var resource_field = user_mapping[1];
    query.where(resource_field, user_id);
};