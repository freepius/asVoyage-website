/**
 * Some javascript for cartography (using Leaflet).
 */

/*global window, L */

(function () {
    "use strict";

    window.asCarto = {
        maps: {},

        layers: {
            OSM: new L.TileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
            }),
            WaterColor: new L.StamenTileLayer('watercolor'),
            BingAerial: new L.BingLayer('Aj6KakrmmAztTyUmXMu7wJnHplOuIYmGbXdd5brEpsAFk3nZL57oPmFgV47nMNHp')
        },

        addMap: function (id, options) {
            var map, attr,

                // default options
                o = {
                    center  : [0.0, 0.0],
                    zoom    : 5,
                    minZoom : 1,
                    maxZoom : 18,
                    layer   : 'WaterColor'
                };

            // Merge default and user options
            for (attr in options) { o[attr] = options[attr]; }

            map = L.map(id, {
                center  : o.center,
                zoom    : o.zoom,
                minZoom : o.minZoom,
                maxZoom : o.maxZoom,
                layers  : [this.layers[o.layer]]
            });

            map.attributionControl.setPrefix('');

            L.control.layers(this.layers).addTo(map);

            this.maps[id] = map;

            return map;
        }
    };

}());
