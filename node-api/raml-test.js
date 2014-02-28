var raml = require('raml-parser');

raml.loadFile('campsite.raml').then( function(data) {
  console.log(data);
}, function(error) {
  console.log('Error parsing: ' + error);
});
