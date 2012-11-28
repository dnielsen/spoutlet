(function($) {
    $.fn.groupsmap = function(options) {
        var geocoder;
        var map;

        var settings = $.extend({
            'address' : '1600 Amphitheatre Parkway Mountain View, CA 94043',
        }, options);

        geocoder = new google.maps.Geocoder();
        var latlng = new google.maps.LatLng(-34.397, 150.644);
        var mapOptions = {
            zoom: 8,
            center: latlng,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        }
        map = new google.maps.Map(document.getElementById(this.attr('id')), mapOptions);

        geocoder.geocode({'address': settings.address}, function(results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                map.setCenter(results[0].geometry.location);
                var marker = new google.maps.Marker({ map: map, position: results[0].geometry.location });
            }
        });
    };
})(jQuery);



