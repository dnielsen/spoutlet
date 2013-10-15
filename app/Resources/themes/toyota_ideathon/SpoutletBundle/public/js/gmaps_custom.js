var map;
var markersArray = [];
var infoArray = [];

function initialize() {

    var mapOptions = {
        center: new google.maps.LatLng(55.1136944, -6.6849769),
        zoom: 1,
        mapTypeId: google.maps.MapTypeId.ROADMAP
    };

    var map = new google.maps.Map(document.getElementById('map-canvas'),
        mapOptions);

    var input = document.getElementById('map-location-search');
    var autocomplete = new google.maps.places.Autocomplete(input);

    autocomplete.bindTo('bounds', map);

    google.maps.event.addListener(autocomplete, 'place_changed', function() {
        input.className = '';
        var place = autocomplete.getPlace();
        if (!place.geometry) {
            // Inform the user that the place was not found and return.
            input.className = 'notfound';
            return;
        }

        // If the place has a geometry, then present it on a map.
        if (place.geometry.viewport) {
            map.fitBounds(place.geometry.viewport);
        } else {
            map.setCenter(place.geometry.location);
            map.setZoom(17);
        }
    });

    if (groupsArray.length > 0) {

        var infowindow = new google.maps.InfoWindow();

        for (i=0; i<groupsArray.length; i++) {

            var contentString = '<div class="map-info">' +
                '<div class="map-info-title"><a href="' + groupsArray[i]['url'] + '">' + groupsArray[i]['name'] + '</a></div>' +
                '<div class="map-info-address1">' + groupsArray[i]['address1'] + '</div>' +
                '<div class="map-info-address2">' + groupsArray[i]['address2'] + '</div>' +
                '<div class="map-info-city">' + groupsArray[i]['city'] + '</div>' +
                '<div class="map-info-state">' + groupsArray[i]['stateProvince'] + '</div>' +
            '</div>';

            var marker = new google.maps.Marker({
                position: new google.maps.LatLng (groupsArray[i]['lat'], groupsArray[i]['long']),
                title: groupsArray[i]['name'],
                map: map,
                info: contentString
            });

            markersArray.push(marker);

            google.maps.event.addListener(marker, 'click', function() {
                infowindow.setContent(this.info);
                infowindow.open(map, this);
            });
        }
    }
}

google.maps.event.addDomListener(window, 'load', initialize);
