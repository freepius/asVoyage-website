/**
 * Some javascript for Base home page
 */

/*jslint regexp: true */
/*global document, $, L */

(function () {
    "use strict";

    var map;

    function mapPictures() {

        var markers, i, map = $.carto.maps.map;

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

        markers = [
            [47.44900131, 2.92650008],
            [47.24423218, 0.21733333],
            [47.39076614, -0.62978333],
            [47.39181519, -0.41446668],
            [46.65703201, 1.26265001],
            [46.15250015, 1.57414997],
            [44.07866669, 3.73656678],
            [43.83348465, 5.59130001],
            [44.32215118, 5.70480013],
            [47.31346512, -1.96768332],
            [45.40963364, 4.57779980],
            [46.15141678, 5.86280012],
            [47.33746719, -1.93145001],
            [44.76426697, 4.21436691],
            [45.56359863, 4.26998329],
            [45.57881546, 4.25571680],
            [45.98379898, 4.03393316],
            [46.80279922, 3.52193332],
            [46.98231506, 3.16971660],
            [47.21666718, 2.98711658]
        ].map(function (latLng) {
            return resetMarker(
                L.marker(latLng, {clickable: false}).addTo(map)
            );
        });

        highlightMarker(markers[0]);

        $('.carousel-inner').on('slide.bs.carousel', function (e) {
            var i = $(e.relatedTarget).data('offset'),
                prev = (i - 1) >= 0 ? (i - 1) : (markers.length - 1);

            resetMarker(markers[prev]);
            highlightMarker(markers[i]);
        });
    }

    function mapCurrentPlace() {

        var map = $.carto.maps.map,

            currentPlace = [47.31346512, -1.96768332], // Lavau-sur-Loire, France

            marker = L.marker(currentPlace, {
                clickable: false,
                icon: L.AwesomeMarkers.icon({ color: 'green' }),
                zIndexOffset: 1000
            });

        $('#current-place').mouseenter(function () {
            map.addLayer(marker);
        });

        $('#current-place').mouseleave(function () {
            map.removeLayer(marker);
        });

        $('#current-place').click(function (e) {
            e.preventDefault();

            map.setView(currentPlace, map.getMaxZoom());
        });

    }

    $(document).ready(function () {

        $('.carousel-inner').carousel();

        // centered on France
        $.carto.addMap('map', {
            center: [46.0, 2.0],
            minZoom: 4,
            maxZoom: 8
        });

        mapPictures();

        mapCurrentPlace();

    });

}());
