var Type = function () {
    return;
};
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
    this.validate = validator || function () {
        return;
    };
    this.default_filter = default_filter;
    this.prefix_filters = prefix_filters;
};

Type.prototype.apply_filter = function (column, query, value) {
    var filter = this.default_filter;
    var raw_value = value;

    //check for filtered prefix
    var prefix_filters = this.prefix_filters;
    var prefix, index;
    /*jslint forin: true*/
    for (prefix in prefix_filters) {
        if (!prefix_filters.hasOwnProperty(prefix)) { continue; }

        index = value.indexOf(prefix);
        if (index !== -1) {
            filter = prefix_filters[prefix];
            raw_value = value.substring(prefix.length);
            break;
        }
    }

    if (!this.validate(raw_value)) {
        throw new Error("'" + raw_value + "' invalid");
    }

    filter(column, query, raw_value);
};


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
        query.where(column, 'LIKE', value);
    },

    //prefix filters
    {
        '~': function (column, query, value) {
            query.where(column, 'LIKE', "%" + value + "%");
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