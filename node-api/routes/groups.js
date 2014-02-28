var restify  = require('restify'),
    db   = require('../database');

exports.findAll = function(req, res, next){
    if (db.conn) {
        var sql = db.getAll('pd_groups', '*');
        db.query(sql, function(err, results) {
            if (err) {
                return next(new restify.RestError(err));
            } else if (results === undefined) {
                return next(new restify.ResourceNotFoundError());
            }
            res.send(results);
        });
    }
};

exports.findById = function(req, res, next) {
    var id = req.params.id;
    if (isNaN(id)) {
        return next(new restify.InvalidArgumentError('id must be a number: '+id));
    }
    
    if (db.conn) {
        var sql = db.get('pd_groups', '*', id);
        db.query(sql, function(err, results) {
            if (err) {
                return next(new restify.RestError(err));
            } else if (results === undefined || results.length == 0) {
                return next(new restify.ResourceNotFoundError(id));
            }
            res.send(results[0]);
        });
    }
};
