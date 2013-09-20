/**
 * Some javascript for Base map page
 */

/*jslint regexp: true */
/*global L, asCarto, geoCoords, labels, hasMessage, getClusterLabel */

(function () {
    "use strict";

    var map = asCarto.addMap('map', {layer: 'OSM'}),

        // Path already traveled of the current trip
        path = L.polyline(geoCoords, {clickable: false, color: 'blue', weight: 8}),

        // Entries of the travel register are clustered
        cluster = L.markerClusterGroup({
            minZoom: 5,
            showCoverageOnHover: false,
            disableClusteringAtZoom: 10
        }),

        // Some icons
        globeIcon        = L.AwesomeMarkers.icon({color: 'orange', icon: 'globe'}),
        envelopeIcon     = L.AwesomeMarkers.icon({color: 'orange', icon: 'envelope-alt'}),
        currentPlaceIcon = L.AwesomeMarkers.icon({color: 'red',    icon: 'smile'}),

        i,
        currentPlace,
        markers = [];

    // Create a marker for each travel register entry
    for (i = 1; i < geoCoords.length; i += 1) {
        markers.push(
            L.marker(geoCoords[i])
                .setIcon(hasMessage[i] ? envelopeIcon : globeIcon)
                .bindLabel(labels[i])
                .addTo(cluster)
        );
    }

    // Add the current place
    currentPlace = L.marker(geoCoords[0])
        .setIcon(currentPlaceIcon)
        .bindLabel(labels[0])
        .addTo(map);

    // Center the map onto this current place.
    map.addLayer(path)
        .addLayer(cluster)
        .setView(currentPlace.getLatLng(), 5, {reset: true});

    /**
     * For each cluster, display (on "mouseover" event) an overview of its markers.
     */
    cluster.on('clustermouseover', function (e) {
        if (!e.layer.getLabel()) {
            e.layer.bindLabel(
                getClusterLabel(e.layer.getAllChildMarkers())
            );
        }
    });

    /**
     * Under the zoom level 5, hide the cluster of travel register entries.
     */
    map.on('zoomend', function () {
        if (map.getZoom() < 5) {
            if (map.hasLayer(cluster)) {
                map.removeLayer(cluster);
            }
        } else {
            if (!map.hasLayer(cluster)) {
                map.addLayer(cluster);
            }
        }
    });

}());
