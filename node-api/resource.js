var restify = require('restify'),
    util    = require('./util');
    knex    = require('./common').knex;

// var operators = ['=', '<', '>', '<=', '>=', 'like', 'not like', 'between', 'ilike']


//Constructor
var Resource = function (spec) {
    this.tableName = spec.tableName;
    this.defaultFields = spec.defaultFields;
    this.allowedFields = spec.allowedFields;
    this.deleted_col = spec.deleted_col || false;
    this.filters = spec.filters || { 
        // label: [column_name [, operator]] 
        q: { field: 'name', operator: 'like' }
    };
}

module.exports = Resource

//-------------------------------------------------

Resource.prototype.validateField = function( field ) {
    if(this.allowedFields.indexOf(field) === -1) {
        return false;
    }
    return true;
}

Resource.prototype.validateOperator = function( op ) {
    if(this.operators.indexOf(op) === -1) {
        return false;
    }
    return true;
}

Resource.prototype.processFields = function( query, req) {
    var reqFields = req.query.fields.split(',');
    for(i in reqFields) {
        var field = reqFields[i];
        if(!this.validateField(field)) {
            throw new restify.InvalidArgumentError(field);
        }
    }
    query.column(reqFields);
}

Resource.prototype.processBasicQueryParams = 
   function(req, query) {
        if(req.query.hasOwnProperty('fields')) {
            this.processFields( query, req );
        } else if(req.query.hasOwnProperty('verbose')) {
            query.column(this.allowedFields);
        } else {
            query.column(this.defaultFields);
        }
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
                
                if(!this.validateField(field))
                    throw new restify.InvalidArgumentError("sort_by field not recognized");
                
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


getTotalCount = function (tableName, deleted_col, callback) {
    var query = knex(tableName).count('*');
    if(deleted_col)
        query.where(deleted_col,0);
    query.exec(callback);
};

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
    
    query.exec(function(err, findAllResultSet) {
        if (err) {
            return next(new restify.RestError(err));
        } else if (findAllResultSet === undefined) {
            return next(new restify.ResourceNotFoundError());
        }
        
        //Set length of results
        resp.header('X-Length', findAllResultSet.length);
        
        //Set length of all results
        //console.log('deleted_col: ' + that.deleted_col);
        this.getTotalCount(that.tableName, that.deleted_col, function(err, totalCountResultSet) {
            if (err) {
                throw new restify.RestError(err);
            } else if (totalCountResultSet === undefined) {
                new restify.ResourceNotFoundError();
            }
            var count = totalCountResultSet[0]["count(*)"];
            resp.header('X-Total-Length', count);
            
            resp.send(findAllResultSet);
        });
        
    });
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
    
    query.exec(function(err, resultSet) {
        if (err) {
            return next(new restify.RestError(err));
        } else if (resultSet === undefined || resultSet.length == 0) {
            return next(new restify.ResourceNotFoundError(id));
        }
        resp.send(resultSet[0]);
    });
};

