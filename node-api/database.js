var mysql   = require('mysql');

exports.connect = function(connectParams) {
    exports.conn = mysql.createConnection(connectParams);
        
    exports.conn.connect(function(err) {
        // connected! (unless `err` is set)
        if (err) {
            console.log("DB conn error...");
        } else {
            console.log("DB connected! ");
        }
    });
    
    exports.conn.on('error', function(err) {
        if (!err.fatal) {
            return;
        }

        if (err.code !== 'PROTOCOL_CONNECTION_LOST') {
            throw err;
        }
        console.log('Re-connecting lost connection: ' + err.stack);
        exports.conn = mysql.createConnection(connectParams);
        handleDisconnect(conn);
        exports.conn.connect();
    });
};

exports.query = function(queryStr, callback) {
    console.log(sql);
    return exports.conn.query(queryStr, callback);
}

exports.get = function(table, columns, id) {
console.log(columns == exports.ALL);
    if(columns == "*")
        sql = mysql.format('SELECT * FROM ?? WHERE id = ?',[table, id]);
    else
        sql = mysql.format('SELECT ?? FROM ?? WHERE id = ?',[columns, table, id]);
    return sql;
};

exports.getAll = function(table, columns, callback) {
    if(columns == "*")
        sql = mysql.format('SELECT * FROM ??',[table]);
    else
        sql = mysql.format('SELECT ?? FROM ??',[columns, table]);
    return sql;
};



