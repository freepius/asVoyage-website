/**
 * Some javascript for Base home page
 */

/*jslint regexp: true */
/*global document, $, L, asCarto, currentPlace, register */

(function () {
    "use strict";

    // Map centered on the current place
    var map = asCarto.addMap('map', {
            center: currentPlace,
            minZoom: 2,
            maxZoom: 8
        }),

        // Path already traveled
        path = L.polyline(register.geoCoords, {
            clickable : false,
            color     : 'blue',
            weight    : 8
        }).addTo(map);

    /**
     * Display and handle the "current place" marker.
     */
    function mapCurrentPlace() {

        var marker = L.marker(currentPlace, {
                clickable: false,
                icon: L.AwesomeMarkers.icon({color: 'red', icon: 'smile'}),
                zIndexOffset: 1000
            }).addTo(map);

        $('#current-place').mouseenter(function () {
            map.setView(currentPlace, 5);
        });

        $('#current-place').click(function (e) {
            e.preventDefault();
            map.setView(currentPlace, map.getMaxZoom());
        });
    }

    $(document).ready(function () {
        $('.carousel-inner').carousel();
        mapCurrentPlace();
    });

}());
