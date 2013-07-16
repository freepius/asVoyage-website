/**
 * Some javascript for cartography (using Leaflet).
 */

/*global document, $, L */

(function () {
    "use strict";

    $.carto = {};

    $.carto.maps = {};

    $.carto.layers = {
        WaterColor : new L.StamenTileLayer('watercolor'),
        Standard   : new L.TileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png')
    };

    $.carto.addMap = function (id, options) {
        var o = $.extend({
                center  : [0.0, 0.0],
                zoom    : 5,
                minZoom : 1,
                maxZoom : 12,
                layer   : 'WaterColor'
            }, options),

            map = L.map(id, {
                center  : o.center,
                zoom    : o.zoom,
                minZoom : o.minZoom,
                maxZoom : o.maxZoom,
                layers  : [$.carto.layers[o.layer]]
            });

        map.attributionControl.setPrefix('Map data © OpenStreetMap contributors');

        L.control.layers($.carto.layers).addTo(map);

        $.carto.maps[id] = map;

        return map;
    };

}());
