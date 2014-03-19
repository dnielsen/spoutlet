var __ = require('underscore');

var Type = function () { return; };
module.exports = Type;


//---------------------------------------------
//-------- Primitave Type Declarations -------- 
//---------------------------------------------
Type.Int = new Type();
Type.Bool = new Type();
Type.Str = new Type();
Type.Date = new Type();

//--------------------------------------------



Type.prototype.init = function (validator, default_filter, prefix_filters) {
    this.validate = validator || function () { return; };
    this.default_filter = default_filter;
    this.prefix_filters = prefix_filters;
};

Type.prototype.apply_filter = function (column, query, value) {
    var filter = this.default_filter;
    var raw_value = value;

    __.find(this.prefix_filters, function (pf, prefix) {
        if (value.indexOf(prefix) === 0) {
            filter = pf;
            raw_value = value.substring(prefix.length);
            return true;
        }
        return false;
    });

    //validate or die
    if (!this.validate(raw_value)) { throw new Error("'" + raw_value + "' invalid"); }

    //apply filter
    filter(column, query, raw_value);
};

// NOTE: The order in which you define your prefix filters is important. 
// Ensure that 
//--------------------------- Primitive Types --------------------------------

Type.Int.init(
    //validator
    function (val) {
        return !isNaN(val);
    },

    //default filter
    function (column, query, value) {
        query.where(column, value);
    },

    //prefix filters
    {
        '>=': function (column, query, value) {
            query.where(column, '>=', value);
        },
        '<=': function (column, query, value) {
            query.where(column, '<=', value);
        },
        '<': function (column, query, value) {
            query.where(column, '<', value);
        },
        '>': function (column, query, value) {
            query.where(column, '>', value);
        },
    }
);


Type.Bool.init(
    //validator
    function (val) {
        if (typeof val === 'boolean' || val === '1' || val === '0') {
            return true;
        }
        val = val.toLowerCase();
        return val === 'true' || val === 'false';
    },

    //default filter
    function (column, query, value) {
        value = value.toLowerCase();
        var real_value = (value === 'true' || value === '1') ? true : false;
        query.where(column, '=', real_value);
    },

    //prefix filters
    {}
);


Type.Str.init(
    //validator
    function (val) {
        return val !== "";
    },

    //default filter
    function (column, query, value) {
        query.where(column, 'like', value);
    },

    //prefix filters
    {
        '!~': function (column, query, value) {
            query.where(column, 'not like', "%" + value + "%");
        },
        '~': function (column, query, value) {
            query.where(column, 'like', "%" + value + "%");
        },
        '!': function (column, query, value) {
            query.where(column, '<>', value);
        }
    }
);


Type.Date.init(
    //validator
    function (val) {
        return !isNaN((new Date(val)).valueOf());
    },

    //default filter
    function (column, query, value) {
        query.where(column, value);
    },

    //prefix filters
    {
        '<=': function (column, query, value) {
            query.where(column, '<=', new Date(value));
        },
        '>=': function (column, query, value) {
            query.where(column, '>=', new Date(value));
        },
        '<': function (column, query, value) {
            query.where(column, '<', new Date(value));
        },
        '>': function (column, query, value) {
            query.where(column, '>', new Date(value));
        },
    }
);