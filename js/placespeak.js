// Get the app key from the hidden input div
var app_key = jQuery('#app_key').val();
var plugin_directory = jQuery('#url_directory').val();
// AJAX call to check for logged in state and to get out the app information
jQuery.ajax({
  url: "http://dev.placespeak.com/connect/check_login_and_api?app_key="+app_key,
  dataType: 'jsonp',
}).done(function(data) {
    if(data.error) {
        // If error, such as not being logged in
        console.log(data.error);
    } else {
        // Do another AJAX call with what was returned for the user_id, this time to a plugin file that returns DB info
        jQuery.ajax({
          url: plugin_directory + 'signed_in_ajax.php?user_id=' + data[0].user_id,
          dataType: 'jsonp',
        }).done(function(data) {
            if(data.error) {
                console.log(data.error);
            } else {
                // Autofill the form and add input fields
                // ID is always "author" by default
                jQuery('#author').val(data.first_name + ' ' + data.last_name);
                jQuery('#placespeak_connect_button').after('<input type="hidden" name="placespeak_verifications" value="'+data.verifications+'">');
            }
        });
        // Data returned consists of various green dots and so on
        jQuery('#placespeak_plugin_map').show();
        jQuery('#powered_by_placespeak').show();
        jQuery('#verified_by_placespeak').show();
        jQuery('#placespeak_verified_info').width(jQuery('#placespeak_plugin_map').width()-40);
        jQuery('#placespeak_verified_question').hover(function() {
            jQuery('#placespeak_verified_info').fadeToggle();
        });
        // Simple leaflet map being loaded
        var map = L.map('placespeak_plugin_map', { zoomControl:false }).setView([51.505, -0.09], 13);
        L.tileLayer('http://api.tiles.mapbox.com/v4/victorplacespeak.cig2i6les1d5kt4kx6sveyeyu/{z}/{x}/{y}.png?access_token=pk.eyJ1IjoidmljdG9ycGxhY2VzcGVhayIsImEiOiJjaWcyaTZteG8xZGl1dTNtNHEzZjdiazlqIn0.KUYzQqUkEAhAaqi0LPMSpQ', {
            //attribution: 'Imagery from <a href="http://mapbox.com/about/maps/">MapBox</a> &mdash; Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            subdomains: 'abcd',
            maxZoom: 19
        }).addTo(map);
        jQuery('#placespeak_connect_button').hide();
        //jQuery('#ps-app-display').append('<h4>'+data[0].name+'</h4>');
        // Decoding polygon and placing on map
        var polygons = [];
        data[0].encoded_polygons.forEach(function(element,index,array) {
            console.log(element);
            var decodedPoly = L.PolylineUtil.decode(element.encoded_polygon);
            var decodedWithItemRemoved = decodedPoly.splice(1,decodedPoly.length);
            var polygon = L.polygon(decodedWithItemRemoved, {
                fillColor: '#9CCBCF',
                color: "#000",
                weight: 0.1,
                opacity: 0.7,
                fillOpacity: 0.7
            }).bindPopup(element.label);
            polygons.push(polygon);
        });
        var polyLayer = L.featureGroup(polygons).addTo(map);
        // Going over green dots and adding them
        var markers = []
        var greenDotIcon = L.icon({
            iconUrl: plugin_directory+'/img/green_dot.png',
        });
        data[0].connected_participants.forEach(function(element,index,array) {
            markers.push(L.marker(element, {icon: greenDotIcon}));
        });
        var markerLayer = L.featureGroup(markers).addTo(map);
        
        // Reorienting map
        map.fitBounds(polyLayer.getBounds());
    }
});