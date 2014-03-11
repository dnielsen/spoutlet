var Type = function(validator, default_filter, prefix_filters) {
	this.validate = validator;
	this.default_filter = default_filter;
	this.prefix_filters = prefix_filters;
}

module.exports = Type;

Type.prototype.apply_filter = function(column, query, value) {
	var filter = this.default_filter;
	var raw_value = value;

	//check for filtered prefix
	var prefixes = Object.keys(this.prefix_filters);
	for(i in prefixes) {
		var prefix = prefixes[i];
		var index = value.indexOf(prefix);
		if(index !== -1) {
			filter = this.prefix_filters[prefix];
			raw_value = value.substring(prefix.length);
			break;
		}
	}

	if(!this.validate(raw_value))
		throw new Error("'" + raw_value + "' invalid");

	filter(column, query, raw_value);
}

Type.Int = 	new Type( 
	
	//validator
	function(val){ return !isNaN(val); },

	//default filter
	function(column, query, value) { query.where(column, value); },
	
	//prefix filters
	{   '>=': function(column, query, value) { query.where(column, '>=', value); },
		'<=': function(column, query, value) { query.where(column, '<=', value); },
		'<' : function(column, query, value) { query.where(column, '<', value); }, 
		'>' : function(column, query, value) { query.where(column, '>', value); },
	});


Type.Bool = new Type( 

	//validator
	function(val){ 
		if(typeof val === 'boolean' || val === '1' || val === '0') 
			return true;
		val = val.toLowerCase();
		return val === 'true' || val === 'false' ; 
	},

	//default filter
	function(column, query, value) { 
		var value = value.toLowerCase();
		real_value = value === 'true' || value === '1' ? true : false;
		query.where(column, '=', real_value ); },

	//prefix filters
	{});


Type.Str = 	new Type( 
	//validator
	function(val){ return val !== ""; }, 
	
	//default filter
	function(column, query, value) { query.where(column, 'LIKE', value); },
	
	//prefix filters
	{ '~': function(column, query, value) { query.where(column, 'LIKE', "%" + value + "%"); } 
	} );



Type.Date = new Type( 
	//validator
	function(val){ return !isNaN((new Date(val)).valueOf()); },  

	//default filter
	function(column, query, value) { query.where(column, value); }, 

	//prefix filters
	{   '<=': function(column, query, value) { query.where(column, '<=', new Date(value)); },
		'>=': function(column, query, value) { query.where(column, '>=', new Date(value)); },
		'<' : function(column, query, value) { query.where(column, '<', new Date(value)); }, 
		'>' : function(column, query, value) { query.where(column, '>', new Date(value)); },
	});
