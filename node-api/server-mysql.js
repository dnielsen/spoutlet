var http = require('http');
var mysql = require("mysql");

var connection = mysql.createConnection({
  host : '127.0.0.1',
  port : 3306,
  database : 'campsite',
  user : 'root',
  password : 'sqladmin'
});

connection.connect(function(err) {
  if(err != null) {
    res.end('Error connection to mysql:' + err + '\n');
  }
});

http.createServer( function(req,res) {
  res.writeHeader(200);
  res.write('Connect to mySql\n');
  
  connection.query("SELECT * from campsite.pd_groups", function(err, rows) {
    if(err != null) {
      res.end("Query error: " + err);
    } else {
      console.log(rows[0]);
 //     connection.end();
    }
    res.end("Success!");
  });
}).listen(8080);
