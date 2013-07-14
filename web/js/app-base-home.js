/**
 * Some javascript for Base home page
 */

/*jslint regexp: true */
/*global document, $, L */

(function () {
    "use strict";

    var map;

    function mapPictures() {

        var markers, i;

        function resetMarker(marker) {
            // TODO : régler le chainage quand mon pullrequest sera accepté et leaflet MAJ
            marker.setZIndexOffset(0)
                .setIcon(L.AwesomeMarkers.icon({color: 'blue', icon: null}));
            marker.setOpacity(0.2);
            return marker;
        }
        function highlightMarker(marker) {
            // TODO : régler le chainage quand mon pullrequest sera accepté et leaflet MAJ
            marker.setZIndexOffset(250)
                .setIcon(L.AwesomeMarkers.icon({color: 'red', icon: null}));
            marker.setOpacity(1.0);
            return marker;
        }

        markers = [
            [47.2694, 2.5559],
            [47.14654, 0.13104],
            [47.23446, -0.37787],
            [47.23509, -0.24868],
            [46.39422, 1.15759],
            [46.09150, 1.34449],
            [44.04720, 3.44194],
            [43.49371, 5.37716],
            [44.19329, 5.42288],
            [47.17808, -1.58061],
            [45.24578, 4.34668],
            [46.09085, 5.51768],
            [47.20248, -1.55887],
            [44.45856, 4.12862],
            [45.33816, 4.16199],
            [45.34729, 4.15343],
            [45.59028, 4.02036],
            [46.48168, 3.31316],
            [46.58939, 3.10183],
            [47.13000, 2.59227]
        ].map(function (latLng) {
            return resetMarker(
                L.marker(latLng, {clickable: false}).addTo(map)
            );
        });

        highlightMarker(markers[0]);

        $('.carousel').on('slide', function (e) {
            var i = $(e.relatedTarget).data('offset'),
                prev = (i - 1) >= 0 ? (i - 1) : (markers.length - 1);

            resetMarker(markers[prev]);
            highlightMarker(markers[i]);
        });
    }

    function mapCurrentPlace() {

        var marker = L.marker([47.18067, -1.57804], {
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

    }

    $(document).ready(function () {

        $('.carousel').carousel();

        map = L.map('map-pictures', {
            center: [46.32433, 2.0], // center of France
            zoom: 5,
            minZoom: 4,
            maxZoom: 8,
            layers: [new L.StamenTileLayer("watercolor")]
        });

        map.attributionControl.setPrefix('');

        mapPictures();

        mapCurrentPlace();

    });

}());
