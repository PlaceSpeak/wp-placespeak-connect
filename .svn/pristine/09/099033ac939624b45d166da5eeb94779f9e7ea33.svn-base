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
}).done(function(placespeak_data) {
    if(placespeak_data.error) {
        // If error, such as not being logged in
        console.log(placespeak_data.error);
        // Various UI modifications
        $('#pre_verified_by_placespeak').show();
        $('#placespeak_pre_verified_info').width($('#placespeak_connect_button').width()-40);
        $('#placespeak_pre_verified_question').hover(function() {
            $('#placespeak_pre_verified_info').fadeToggle();
        });
    } else {
        // If successful at being logged in, do another AJAX call to get out specific user information
        $.ajax({
          url: '/?placespeak_oauth=check&user_id=' + placespeak_data[0].user_id + '&app_key='+app_key,
          dataType: 'jsonp',
        }).done(function(data) {
            if(data.error) {
                console.log(data.error);
            } else {
                // Do map here; if I put outside this AJAX call, it will run even if user doesn't exist in a DB yet
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
                
                L.tileLayer('https://api.mapbox.com/v4/mapbox.streets/{z}/{x}/{y}.png?access_token=pk.eyJ1IjoicGxhY2VzcGVhayIsImEiOiJjaWw0YnJvdTIzeDY3dXlrczY3YmRlMGU3In0.X9KRXYQzCQMVIhYqrI4RaQ', {
                    //attribution: 'Imagery from <a href="http://mapbox.com/about/maps/">MapBox</a> &mdash; Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    subdomains: 'abcd',
                    maxZoom: 19
                }).addTo(map);
                // Decoding polygon and placing on map
                var polygons = [];
                // GeoJSON formatting
                var thisGeoJSON = JSON.parse(placespeak_data[0].geojson_polygons);
                var thisGeoJSONLayer = L.geoJson(thisGeoJSON, {
                    style: {        
                        fillColor: '#9CCBCF',     
                        color: "#000",        
                        weight: 0.1,      
                        opacity: 0.7,     
                        fillOpacity: 0.7      
                   }
                }).addTo(map);
                // Going over green dots and adding them
                var markers = []
                var greenDotIcon = L.icon({
                    iconUrl: plugin_directory+'/img/green_dot.png',
                });
                placespeak_data[0].connected_participants.forEach(function(element,index,array) {
                    markers.push(L.marker(element, {icon: greenDotIcon}));
                });
                var markerLayer = L.featureGroup(markers).addTo(map);

                // Reorienting map to fit polygon bounds
                map.fitBounds(thisGeoJSONLayer.getBounds());
                
                // Start refilling form fields as necessary
                $('#author').val(data.first_name + ' ' + data.last_name);
                // Refill the form with values from localStorage if they exist, then delete keys and information
                $('#placespeak_connect_button').closest("form").each(function() {
                    $(this).find(':input').each(function() {
                        if($(this).attr("name")&&$(this).attr("name")!=='submit'&&localStorage.getItem($(this).attr("name"))) {
                            $(this).val(localStorage.getItem($(this).attr("name")));
                        }
                    });
                });
                var localStorageString = localStorage.getItem('keyNames');
                if(localStorageString !== null) {
                    var localStorageArray = localStorageString.split(',');
                    localStorageArray.forEach(function(element,index,array) {
                        localStorage.removeItem(element);
                    });
                    localStorage.removeItem('keyNames');
                }
                // Add a little thing saying they are inside/outside consultation areas, and name of labels
                if(data.geo_labels) {
                    $('#powered_by_placespeak').after('<div style="margin-top:10px;"><p>Your location is inside the consultation area(s) ('+data.geo_labels+').</p></div>');
                } else {
                    $('#powered_by_placespeak').after('<div style="margin-top:10px;"><p>Your location is not inside the consultation area(s).</p></div>');
                    data.geo_labels = 'None';
                }
                // Autofill the form and add input fields (verification levels, geo_labels for this app, and user id)
                // ID is always "author" by default in WP comment area
                $('#placespeak_connect_button').after("<input type='hidden' name='placespeak_verifications' value='"+data.verifications+"'>");
                $('#placespeak_connect_button').after('<input type="hidden" name="placespeak_user_name" value="'+data.first_name+' '+data.last_name+'">');
                $('#placespeak_connect_button').after('<input type="hidden" name="placespeak_geo_labels" value="'+ data.geo_labels +'">');
                $('#placespeak_connect_button').after('<input type="hidden" name="placespeak_user_id" value="'+data.user_id+'">');
            }
        });
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
                keyNames.push(jQuery(this).attr("name"));
            }
        });
        localStorage.setItem('keyNames',keyNames);
    });
    return true;
}