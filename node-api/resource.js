var restify = require('restify'),
    util    = require('./util');
    knex    = require('./common').knex;
    
function processFields( query, req, allowedFields) {
    var reqFields = req.query.fields.split(',');
    for(i in reqFields) {
        var field = reqFields[i];
        if(allowedFields.indexOf(field) === -1) {
            throw new restify.InvalidArgumentError(field);
        }
    }
    query = query.select(reqFields);
}

module.exports.processBasicQueryParams =
               processBasicQueryParams = 
   function (req, query, allowedFields, defaultFields) {
        if(req.query.hasOwnProperty('fields')) {
            processFields( query, req, allowedFields );
        } else if(req.query.hasOwnProperty('verbose')) {
            query = query.select(allowedFields);
        } else {
            query = query.select(defaultFields);
        }
    }

module.exports.processCollectionQueryParams = 
    function (req, query, allowedFields, defaultFields) {
    
        //inherit base query parameters
        processBasicQueryParams(req, query, allowedFields, defaultFields);
        
        if(req.query.hasOwnProperty('limit')) {
            var limit = req.query.limit;
            if(isNaN(limit))
                throw new restify.InvalidArgumentError(limit);
                
            query = query.limit(limit);
            
            if(req.query.hasOwnProperty('offset')) {
                var offset = req.query.offset;
                if(isNaN(offset))
                    throw new restify.InvalidArgumentError(offset);
                    
                query = query.offset(offset);
            }
        }
        
        if(req.query.hasOwnProperty('sort_by')) {
            var offset = req.query.offset;
            if(isNaN(offset))
                throw new restify.InvalidArgumentError(offset);
                
            query = query.offset(offset);
        }
    }

