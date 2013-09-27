/**
 * Some javascript for Base map page
 */

/*jslint regexp: true */
/*global L, asCarto, register, media, getClusterLabel, OverlappingMarkerSpiderfier */

(function () {
    "use strict";

    var map = asCarto.addMap('map', {layer: 'OSM', scale: true}),

        // Path already traveled
        path = L.polyline(register.geoCoords, {
            clickable : false,
            color     : 'blue',
            weight    : 8
        }),

        // Entries of the travel register are clustered
        cluster = L.markerClusterGroup({
            showCoverageOnHover: false,
            disableClusteringAtZoom: 10
        }),

        // Layer containing the media element markers
        mediaLayer = L.layerGroup(),

        // Media element markers will be spiderfied
        oms = new OverlappingMarkerSpiderfier(map),

        // Some icons
        globeIcon        = L.AwesomeMarkers.icon({color: 'orange', icon: 'globe'}),
        envelopeIcon     = L.AwesomeMarkers.icon({color: 'orange', icon: 'envelope-alt'}),
        currentPlaceIcon = L.AwesomeMarkers.icon({color: 'red',    icon: 'smile'}),
        mediaIcon        = L.AwesomeMarkers.icon({color: 'blue',   icon: 'camera'}),

        // Various variables
        i,
        currentPlace,
        marker,
        popup = new L.Popup({minWidth: 120, maxWidth: 400});

    /**
     * Create a marker for each travel register entry.
     * Then, add it to the cluster.
     */
    for (i = 1; i < register.geoCoords.length; i += 1) {
        L.marker(register.geoCoords[i])
            .setIcon(register.hasMessage[i] ? envelopeIcon : globeIcon)
            .bindLabel(register.labels[i])
            .addTo(cluster);
    }

    /**
     * Add the current place
     */
    currentPlace = L.marker(register.geoCoords[0], {zIndexOffset: 100})
        .setIcon(currentPlaceIcon)
        .bindLabel(register.labels[0])
        .addTo(map);

    // When clicked => zoom in on it !
    currentPlace.on('click', function () {
        map.setView(currentPlace.getLatLng(), Math.max(12, map.getZoom()));
    });

    // Initially, center the map onto this current place.
    map.setView(currentPlace.getLatLng(), 5, {reset: true});

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

    /**
     * Create a marker for each media element
     */
    for (i = 0; i < media.geoCoords.length; i += 1) {

        marker = L.marker(media.geoCoords[i], {riseOnHover: true})
            .setIcon(mediaIcon)
            .addTo(mediaLayer);

        marker.thumb   = media.thumb[i];
        marker.content = media.content[i];

        oms.addMarker(marker);
    }

    // Display a popup when a media element marker is clicked
    oms.addListener('click', function (marker) {
        popup.setContent(marker.content)
            .setLatLng(marker.getLatLng())
            .openOn(map);
    });

    // For media element markers, create labels when spiderfy
    oms.addListener('spiderfy', function (markers) {
        for (i = 0;  i < markers.length; i += 1) {
            markers[i].bindLabel(markers[i].thumb, {className: 'media-label'});
        }
    });

    // For media element markers, destroy labels when unspiderfy
    oms.addListener('unspiderfy', function (markers) {
        for (i = 0;  i < markers.length; i += 1) {
            markers[i].unbindLabel();
        }
    });

    /**
     * Add a control (always visible) to show/hide the media layer
     */
    L.control.layers(
        null,
        {"Photos <i class='icon-camera'></i>": mediaLayer},
        {collapsed: false}
    ).addTo(map);

    /**
     * Finally, add layers
     */
    map.addLayer(path).addLayer(cluster);

}());
