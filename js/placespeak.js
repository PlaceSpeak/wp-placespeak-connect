(function( $ ) {
 'use strict';
/**
 * This file contains the JS required for AJAXing and saving form input
 * 
 */

// Get app key and plugin directory for use in AJAX
var app_key = $('#app_key').val();
var plugin_directory = $('#url_directory').val();

// AJAX call to check for logged in state and to get out the app information
$.ajax({
  url: "https://placespeak.com/connect/check_login_and_api?app_key="+app_key,
  dataType: 'jsonp',
}).done(function(data) {
    if(data.error) {
        // If error, such as not being logged in
        console.log(data.error);
        // Various UI modifications
        $('#pre_verified_by_placespeak').show();
        $('#placespeak_pre_verified_info').width($('#placespeak_connect_button').width()-40);
        $('#placespeak_pre_verified_question').hover(function() {
            $('#placespeak_pre_verified_info').fadeToggle();
        });
    } else {
        // If successful at being logged in, do another AJAX call to get out specific user information
        $.ajax({
          url: plugin_directory + 'signed_in_ajax.php?user_id=' + data[0].user_id + '&app_key='+app_key,
          dataType: 'jsonp',
        }).done(function(data) {
            if(data.error) {
                console.log(data.error);
            } else {
                // Refill the form with values from localStorage if they exist, then delete keys and information
                console.log('hi');
                $('#placespeak_connect_button').closest("form").each(function() {
                    console.log('found form');
                    $(this).find(':input').each(function() {
                        console.log('found input'+$(this).attr("name"));
                        if($(this).attr("name")) {
                            console.log('replacing input');
                            $(this).val(localStorage.getItem($(this).attr("name")));
                        }
                    });
                });
                var localStorageString = localStorage.getItem('keyNames');
                var localStorageArray = localStorageString.split(',');
                localStorageArray.forEach(function(element,index,array) {
                    localStorage.removeItem(element);
                });
                localStorage.removeItem('keyNames');
                // Autofill the form and add input fields (verification levels, geo_labels for this app, and user id)
                // ID is always "author" by default in WP comment area
                $('#author').val(data.first_name + ' ' + data.last_name);
                $('#placespeak_connect_button').after("<input type='hidden' name='placespeak_verifications' value='"+data.verifications+"'>");
                $('#placespeak_connect_button').after('<input type="hidden" name="placespeak_user_name" value="'+data.first_name+' '+data.last_name+'">');
                $('#placespeak_connect_button').after('<input type="hidden" name="placespeak_geo_labels" value="'+data.geo_labels+'">');
                $('#placespeak_connect_button').after('<input type="hidden" name="placespeak_user_id" value="'+data.user_id+'">');
                // Add a little thing saying they are inside/outside consultation areas, and name of labels
                if(data.geo_labels) {
                    $('#powered_by_placespeak').after('<div style="margin-top:10px;"><p>Your location is inside the consultation area(s) ('+data.geo_labels+').</p></div>');
                } else {
                    $('#powered_by_placespeak').after('<div style="margin-top:10px;"><p>Your location is not inside the consultation area(s).</p></div>');
                }
            }
        });
        // Data returned consists of various green dots, information pop-over, map width
        $('#placespeak_plugin_map').show();
        $('#powered_by_placespeak').show();
        $('#verified_by_placespeak').show();
        $('#placespeak_verified_info').width($('#placespeak_plugin_map').width()-40);
        $('#placespeak_verified_question').hover(function() {
            $('#placespeak_verified_info').fadeToggle();
        });
        $('#placespeak_connect_button').hide();
        // Loading Leaflet
        var map = L.map('placespeak_plugin_map', { zoomControl:false }).setView([51.505, -0.09], 13);
        L.tileLayer('http://api.tiles.mapbox.com/v4/victorplacespeak.cig2i6les1d5kt4kx6sveyeyu/{z}/{x}/{y}.png?access_token=pk.eyJ1IjoidmljdG9ycGxhY2VzcGVhayIsImEiOiJjaWcyaTZteG8xZGl1dTNtNHEzZjdiazlqIn0.KUYzQqUkEAhAaqi0LPMSpQ', {
            //attribution: 'Imagery from <a href="http://mapbox.com/about/maps/">MapBox</a> &mdash; Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            subdomains: 'abcd',
            maxZoom: 19
        }).addTo(map);
        // Decoding polygon and placing on map
        var polygons = [];
        data[0].encoded_polygons.forEach(function(element,index,array) {
            console.log(element);
            var decodedPoly = L.PolylineUtil.decode(element.encoded_polygon);
            var decodedWithItemRemoved = decodedPoly.splice(1,decodedPoly.length);
            // This loop may be unnecessary - specific to a strange bug that occurred in testing
            decodedWithItemRemoved.forEach(function(element2,index2,array2) {
                // Had a bug where something NaN appeared
                if(isNaN(element2[0])||isNaN(element2[1])) {
                    decodedWithItemRemoved.splice(index2,1);
                }
            });
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
        
        // Reorienting map to fit polygon bounds
        map.fitBounds(polyLayer.getBounds());
    }
});
    
})( jQuery );

// If connect button is pressed, store info in localStorage
function saveFormToLocalStorage() {
    // Stores values by getting PlaceSpeak link, then parent form and all input children
    jQuery('#placespeak_connect_button').closest("form").each(function() {
        var keyNames = [];
        jQuery(this).find(':input').each(function() {
            if(jQuery(this).attr("name")&&jQuery(this).val()) {
                localStorage.setItem(jQuery(this).attr("name"), jQuery(this).val());
                keyNames.push($(this).attr("name"));
            }
        });
        localStorage.setItem('keyNames',keyNames);
    });
    return true;
}