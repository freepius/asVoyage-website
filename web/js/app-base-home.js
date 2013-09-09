/**
 * Some javascript for Base home page
 */

/*jslint regexp: true */
/*global document, $, L, asCarto, currentPlace, currentPlaceMessage */

(function () {
    "use strict";

    // Map centered on the (last registered) current place
    var map = asCarto.addMap('map', {
        center: currentPlace,
        minZoom: 2,
        maxZoom: 8
    });

    /**
     * Display on map the markers related to pictures +
     * highlight marker of the current picture.
     */
    function mapPictures() {

        var i, map = asCarto.maps.map, markers;

        function resetMarker(marker) {
            return marker.setZIndexOffset(0)
                .setIcon(L.AwesomeMarkers.icon({color: 'blue', icon: null}))
                .setOpacity(0.2);
        }

        function highlightMarker(marker) {
            return marker.setZIndexOffset(250)
                .setIcon(L.AwesomeMarkers.icon({color: 'red', icon: null}))
                .setOpacity(1.0);
        }

        markers = [].map(function (latLng) {
            return resetMarker(
                L.marker(latLng, {clickable: false}).addTo(map)
            );
        });

        highlightMarker(markers[0]);

        // Highlight marker of the current picture
        $(document).ready(function () {
            $('.carousel-inner').on('slide.bs.carousel', function (e) {
                var i = $(e.relatedTarget).data('offset'),
                    prev = (i - 1) >= 0 ? (i - 1) : (markers.length - 1);

                resetMarker(markers[prev]);
                highlightMarker(markers[i]);
            });
        });
    }

    /**
     * Display and handle the "current place" marker.
     */
    function mapCurrentPlace() {

        var marker = L.marker(currentPlace, {
                icon: L.AwesomeMarkers.icon({ color: 'green' }),
                zIndexOffset: 1000
            }).addTo(map);

        if (currentPlaceMessage) {
            marker.bindPopup(currentPlaceMessage);
        }

        $('#current-place').mouseenter(function () {
            map.setView(currentPlace, 5);
        });

        $('#current-place').click(function (e) {
            e.preventDefault();
            map.setView(currentPlace, map.getMaxZoom());
        });
    }

    /*mapPictures();*/

    $(document).ready(function () {
        $('.carousel-inner').carousel();
        mapCurrentPlace();
    });

}());
