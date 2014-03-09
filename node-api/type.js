var Type = function(validator, filters) {
	this.validate = validator;
}

module.exports = Type;

Type.Int = 	new Type( function(val){ 
	return !isNaN(val); 
} );

Type.Bool = new Type( function(val){ 
	if(typeof val === 'boolean')
		return true;
	val = val.toLowerCase();
	return val === 'true' || val === 'false' || val === '1' || val === '0'; 
} );

Type.Str = 	new Type( function(val){
	return val !== ""; 
} );

Type.Date = new Type( function(val){ 
	return !isNaN((new Date(val)).valueOf()); 
} );

//Type._object = 	new Type();