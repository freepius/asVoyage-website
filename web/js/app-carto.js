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
                attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors, CC-BY-SA'
            }),
            WaterColor: new L.StamenTileLayer('watercolor'),
            BingAerial: new L.BingLayer(window.bingMapsAPIKey)
        },

        addMap: function (id, options) {
            var attr, map,

                // default options
                o = {
                    center   : [0.0, 0.0],
                    zoom     : 5,
                    layer    : 'WaterColor',
                    layers   : [],
                    controls : {
                        pan        : false,
                        scale      : false,
                        zoomslider : false
                    }
                };

            // Merge default and user options
            for (attr in options) { o[attr] = options[attr]; }

            o.layers.push(this.layers[o.layer]);

            // Add ZoomSlider control through options

            if (o.controls.zoomslider) {
                o.zoomsliderControl = true;
                o.zoomControl = false;
            }

            // Initialize a new Leaflet map

            map = L.map(id, o);

            map.attributionControl.setPrefix('');

            L.control.layers(this.layers).addTo(map);

            // Add controls

            if (o.controls.pan) {
                L.control.pan().addTo(map);
            }

            if (o.controls.scale) {
                L.control.scale({maxWidth: 150}).addTo(map);
            }

            this.maps[id] = map;

            return map;
        }
    };

}());
