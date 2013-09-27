(function(){(function(){var b={}.hasOwnProperty,a=[].slice;null!=this.L&&(this.OverlappingMarkerSpiderfier=function(){function c(n,h){var i,l,j,k,m=this;this.map=n;null==h&&(h={});for(i in h){b.call(h,i)&&(l=h[i],this[i]=l)}this.initMarkerArrays();this.listeners={};k=["click","zoomend"];l=0;for(j=k.length;l<j;l++){i=k[l],this.map.addEventListener(i,function(){return m.unspiderfy()})}}var f,e;f=c.prototype;f.VERSION="0.2.5";e=2*Math.PI;f.keepSpiderfied=!1;f.nearbyDistance=20;f.circleSpiralSwitchover=9;f.circleFootSeparation=25;f.circleStartAngle=e/12;f.spiralFootSeparation=28;f.spiralLengthStart=11;f.spiralLengthFactor=5;f.legWeight=1.5;f.legColors={usual:"#222",highlighted:"#f00"};f.initMarkerArrays=function(){this.markers=[];return this.markerListeners=[]};f.addMarker=function(h){var d,g=this;if(null!=h._oms){return this}h._oms=!0;d=function(){return g.spiderListener(h)};h.addEventListener("click",d);this.markerListeners.push(d);this.markers.push(h);return this};f.getMarkers=function(){return this.markers.slice(0)};f.removeMarker=function(h){var d,g;null!=h._omsData&&this.unspiderfy();d=this.arrIndexOf(this.markers,h);if(0>d){return this}g=this.markerListeners.splice(d,1)[0];h.removeEventListener("click",g);delete h._oms;this.markers.splice(d,1);return this};f.clearMarkers=function(){var k,d,h,j,i;this.unspiderfy();i=this.markers;k=h=0;for(j=i.length;h<j;k=++h){d=i[k],k=this.markerListeners[k],d.removeEventListener("click",k),delete d._oms}this.initMarkerArrays();return this};f.addListener=function(i,d){var g,h;(null!=(h=(g=this.listeners)[i])?h:g[i]=[]).push(d);return this};f.removeListener=function(h,d){var g;g=this.arrIndexOf(this.listeners[h],d);0>g||this.listeners[h].splice(g,1);return this};f.clearListeners=function(d){this.listeners[d]=[];return this};f.trigger=function(){var l,d,h,k,i,j;d=arguments[0];l=2<=arguments.length?a.call(arguments,1):[];d=null!=(h=this.listeners[d])?h:[];j=[];k=0;for(i=d.length;k<i;k++){h=d[k],j.push(h.apply(null,l))}return j};f.generatePtsCircle=function(n,h){var i,l,j,k,m;j=this.circleFootSeparation*(2+n)/e;l=e/n;m=[];for(i=k=0;0<=n?k<n:k>n;i=0<=n?++k:--k){i=this.circleStartAngle+i*l,m.push(new L.Point(h.x+j*Math.cos(i),h.y+j*Math.sin(i)))}return m};f.generatePtsSpiral=function(n,h){var i,l,j,k,m;j=this.spiralLengthStart;i=0;m=[];for(l=k=0;0<=n?k<n:k>n;l=0<=n?++k:--k){i+=this.spiralFootSeparation/j+0.0005*l,l=new L.Point(h.x+j*Math.cos(i),h.y+j*Math.sin(i)),j+=e*this.spiralLengthFactor/i,m.push(l)}return m};f.spiderListener=function(r){var s,t,p,n,o,q,m,l,k;s=null!=r._omsData;(!s||!this.keepSpiderfied)&&this.unspiderfy();if(s){return this.trigger("click",r)}n=[];o=[];q=this.nearbyDistance*this.nearbyDistance;p=this.map.latLngToLayerPoint(r.getLatLng());k=this.markers;m=0;for(l=k.length;m<l;m++){s=k[m],t=this.map.latLngToLayerPoint(s.getLatLng()),this.ptDistanceSq(t,p)<q?n.push({marker:s,markerPt:t}):o.push(s)}return 1===n.length?this.trigger("click",r):this.spiderfy(n,o)};f.makeHighlightListeners=function(g){var d=this;return{highlight:function(){return g._omsData.leg.setStyle({color:d.legColors.highlighted})},unhighlight:function(){return g._omsData.leg.setStyle({color:d.legColors.usual})}}};f.spiderfy=function(w,x){var y,u,t,v,n,s,r,q,o,p;this.spiderfying=!0;p=w.length;y=this.ptAverage(function(){var g,d,h;h=[];g=0;for(d=w.length;g<d;g++){r=w[g],h.push(r.markerPt)}return h}());v=p>=this.circleSpiralSwitchover?this.generatePtsSpiral(p,y).reverse():this.generatePtsCircle(p,y);y=function(){var g,d,j,h=this;j=[];g=0;for(d=v.length;g<d;g++){t=v[g],u=this.map.layerPointToLatLng(t),o=this.minExtract(w,function(i){return h.ptDistanceSq(i.markerPt,t)}),s=o.marker,n=new L.Polyline([s.getLatLng(),u],{color:this.legColors.usual,weight:this.legWeight,clickable:!1}),this.map.addLayer(n),s._omsData={usualPosition:s.getLatLng(),leg:n},this.legColors.highlighted!==this.legColors.usual&&(q=this.makeHighlightListeners(s),s._omsData.highlightListeners=q,s.addEventListener("mouseover",q.highlight),s.addEventListener("mouseout",q.unhighlight)),s.setLatLng(u),s.setZIndexOffset(1000000),j.push(s)}return j}.call(this);delete this.spiderfying;this.spiderfied=!0;return this.trigger("spiderfy",y,x)};f.unspiderfy=function(p){var g,j,n,o,m,k,l;null==p&&(p=null);if(null==this.spiderfied){return this}this.unspiderfying=!0;o=[];n=[];l=this.markers;m=0;for(k=l.length;m<k;m++){g=l[m],null!=g._omsData?(this.map.removeLayer(g._omsData.leg),g!==p&&g.setLatLng(g._omsData.usualPosition),g.setZIndexOffset(0),j=g._omsData.highlightListeners,null!=j&&(g.removeEventListener("mouseover",j.highlight),g.removeEventListener("mouseout",j.unhighlight)),delete g._omsData,o.push(g)):n.push(g)}delete this.unspiderfying;delete this.spiderfied;this.trigger("unspiderfy",o,n);return this};f.ptDistanceSq=function(i,d){var g,h;g=i.x-d.x;h=i.y-d.y;return g*g+h*h};f.ptAverage=function(l){var g,h,j,k,i;k=h=j=0;for(i=l.length;k<i;k++){g=l[k],h+=g.x,j+=g.y}l=l.length;return new L.Point(h/l,j/l)};f.minExtract=function(q,j){var k,p,n,o,l,m;n=l=0;for(m=q.length;l<m;n=++l){if(o=q[n],o=j(o),!("undefined"!==typeof k&&null!==k)||o<p){p=o,k=n}}return q.splice(k,1)[0]};f.arrIndexOf=function(m,h){var i,l,j,k;if(null!=m.indexOf){return m.indexOf(h)}i=j=0;for(k=m.length;j<k;i=++j){if(l=m[i],l===h){return i}}return -1};return c}())}).call(this)}).call(this);(function(b,a,c){L.MarkerClusterGroup=L.FeatureGroup.extend({options:{maxClusterRadius:80,iconCreateFunction:null,spiderfyOnMaxZoom:true,showCoverageOnHover:true,zoomToBoundsOnClick:true,singleMarkerMode:false,disableClusteringAtZoom:null,removeOutsideVisibleBounds:true,animateAddingMarkers:false,spiderfyDistanceMultiplier:1,polygonOptions:{}},initialize:function(d){L.Util.setOptions(this,d);if(!this.options.iconCreateFunction){this.options.iconCreateFunction=this._defaultIconCreateFunction}this._featureGroup=L.featureGroup();this._featureGroup.on(L.FeatureGroup.EVENTS,this._propagateEvent,this);this._nonPointGroup=L.featureGroup();this._nonPointGroup.on(L.FeatureGroup.EVENTS,this._propagateEvent,this);this._inZoomAnimation=0;this._needsClustering=[];this._needsRemoving=[];this._currentShownBounds=null},addLayer:function(g){if(g instanceof L.LayerGroup){var h=[];for(var f in g._layers){h.push(g._layers[f])}return this.addLayers(h)}if(!g.getLatLng){this._nonPointGroup.addLayer(g);return this}if(!this._map){this._needsClustering.push(g);return this}if(this.hasLayer(g)){return this}if(this._unspiderfy){this._unspiderfy()}this._addLayer(g,this._maxZoom);var d=g,e=this._map.getZoom();if(g.__parent){while(d.__parent._zoom>=e){d=d.__parent}}if(this._currentShownBounds.contains(d.getLatLng())){if(this.options.animateAddingMarkers){this._animationAddLayer(g,d)}else{this._animationAddLayerNonAnimated(g,d)}}return this},removeLayer:function(e){if(e instanceof L.LayerGroup){var f=[];for(var d in e._layers){f.push(e._layers[d])}return this.removeLayers(f)}if(!e.getLatLng){this._nonPointGroup.removeLayer(e);return this}if(!this._map){if(!this._arraySplice(this._needsClustering,e)&&this.hasLayer(e)){this._needsRemoving.push(e)}return this}if(!e.__parent){return this}if(this._unspiderfy){this._unspiderfy();this._unspiderfyLayer(e)}this._removeLayer(e,true);if(this._featureGroup.hasLayer(e)){this._featureGroup.removeLayer(e);if(e.setOpacity){e.setOpacity(1)}}return this},addLayers:function(f){var j,g,e,n=this._map,d=this._featureGroup,k=this._nonPointGroup;for(j=0,g=f.length;j<g;j++){e=f[j];if(!e.getLatLng){k.addLayer(e);continue}if(this.hasLayer(e)){continue}if(!n){this._needsClustering.push(e);continue}this._addLayer(e,this._maxZoom);if(e.__parent){if(e.__parent.getChildCount()===2){var h=e.__parent.getAllChildMarkers(),o=h[0]===e?h[1]:h[0];d.removeLayer(o)}}}if(n){d.eachLayer(function(i){if(i instanceof L.MarkerCluster&&i._iconNeedsUpdate){i._updateIcon()}});this._topClusterLevel._recursivelyAddChildrenToMap(null,this._zoom,this._currentShownBounds)}return this},removeLayers:function(h){var g,f,d,e=this._featureGroup,j=this._nonPointGroup;if(!this._map){for(g=0,f=h.length;g<f;g++){d=h[g];this._arraySplice(this._needsClustering,d);j.removeLayer(d)}return this}for(g=0,f=h.length;g<f;g++){d=h[g];if(!d.__parent){j.removeLayer(d);continue}this._removeLayer(d,true,true);if(e.hasLayer(d)){e.removeLayer(d);if(d.setOpacity){d.setOpacity(1)}}}this._topClusterLevel._recursivelyAddChildrenToMap(null,this._zoom,this._currentShownBounds);e.eachLayer(function(i){if(i instanceof L.MarkerCluster){i._updateIcon()}});return this},clearLayers:function(){if(!this._map){this._needsClustering=[];delete this._gridClusters;delete this._gridUnclustered}if(this._noanimationUnspiderfy){this._noanimationUnspiderfy()}this._featureGroup.clearLayers();this._nonPointGroup.clearLayers();this.eachLayer(function(d){delete d.__parent});if(this._map){this._generateInitialClusters()}return this},getBounds:function(){var e=new L.LatLngBounds();if(this._topClusterLevel){e.extend(this._topClusterLevel._bounds)}else{for(var d=this._needsClustering.length-1;d>=0;d--){e.extend(this._needsClustering[d].getLatLng())}}var f=this._nonPointGroup.getBounds();if(f.isValid()){e.extend(f)}return e},eachLayer:function(g,e){var f=this._needsClustering.slice(),d;if(this._topClusterLevel){this._topClusterLevel.getAllChildMarkers(f)}for(d=f.length-1;d>=0;d--){g.call(e,f[d])}this._nonPointGroup.eachLayer(g,e)},hasLayer:function(e){if(!e){return false}var d,f=this._needsClustering;for(d=f.length-1;d>=0;d--){if(f[d]===e){return true}}f=this._needsRemoving;for(d=f.length-1;d>=0;d--){if(f[d]===e){return false}}return !!(e.__parent&&e.__parent._group===this)||this._nonPointGroup.hasLayer(e)},zoomToShowLayer:function(d,f){var e=function(){if((d._icon||d.__parent._icon)&&!this._inZoomAnimation){this._map.off("moveend",e,this);this.off("animationend",e,this);if(d._icon){f()}else{if(d.__parent._icon){var g=function(){this.off("spiderfied",g,this);f()};this.on("spiderfied",g,this);d.__parent.spiderfy()}}}};if(d._icon){f()}else{if(d.__parent._zoom<this._map.getZoom()){this._map.on("moveend",e,this);if(!d._icon){this._map.panTo(d.getLatLng())}}else{this._map.on("moveend",e,this);this.on("animationend",e,this);this._map.setView(d.getLatLng(),d.__parent._zoom+1);d.__parent.zoomToBounds()}}},onAdd:function(g){this._map=g;var f,d,e;if(!isFinite(this._map.getMaxZoom())){throw"Map has no maxZoom specified"}this._featureGroup.onAdd(g);this._nonPointGroup.onAdd(g);if(!this._gridClusters){this._generateInitialClusters()}for(f=0,d=this._needsRemoving.length;f<d;f++){e=this._needsRemoving[f];this._removeLayer(e,true)}this._needsRemoving=[];for(f=0,d=this._needsClustering.length;f<d;f++){e=this._needsClustering[f];if(!e.getLatLng){this._featureGroup.addLayer(e);continue}if(e.__parent){continue}this._addLayer(e,this._maxZoom)}this._needsClustering=[];this._map.on("zoomend",this._zoomEnd,this);this._map.on("moveend",this._moveEnd,this);if(this._spiderfierOnAdd){this._spiderfierOnAdd()}this._bindEvents();this._zoom=this._map.getZoom();this._currentShownBounds=this._getExpandedVisibleBounds();this._topClusterLevel._recursivelyAddChildrenToMap(null,this._zoom,this._currentShownBounds)},onRemove:function(d){d.off("zoomend",this._zoomEnd,this);d.off("moveend",this._moveEnd,this);this._unbindEvents();this._map._mapPane.className=this._map._mapPane.className.replace(" leaflet-cluster-anim","");if(this._spiderfierOnRemove){this._spiderfierOnRemove()}this._featureGroup.onRemove(d);this._nonPointGroup.onRemove(d);this._featureGroup.clearLayers();this._map=null},getVisibleParent:function(d){var e=d;while(e!==null&&!e._icon){e=e.__parent}return e},_arraySplice:function(e,f){for(var d=e.length-1;d>=0;d--){if(e[d]===f){e.splice(d,1);return true}}},_removeLayer:function(j,g,l){var h=this._gridClusters,f=this._gridUnclustered,d=this._featureGroup,e=this._map;if(g){for(var k=this._maxZoom;k>=0;k--){if(!f[k].removeObject(j,e.project(j.getLatLng(),k))){break}}}var m=j.__parent,i=m._markers,n;this._arraySplice(i,j);while(m){m._childCount--;if(m._zoom<0){break}else{if(g&&m._childCount<=1){n=m._markers[0]===j?m._markers[1]:m._markers[0];h[m._zoom].removeObject(m,e.project(m._cLatLng,m._zoom));f[m._zoom].addObject(n,e.project(n.getLatLng(),m._zoom));this._arraySplice(m.__parent._childClusters,m);m.__parent._markers.push(n);n.__parent=m.__parent;if(m._icon){d.removeLayer(m);if(!l){d.addLayer(n)}}}else{m._recalculateBounds();if(!l||!m._icon){m._updateIcon()}}}m=m.__parent}delete j.__parent},_propagateEvent:function(d){if(d.layer instanceof L.MarkerCluster){d.type="cluster"+d.type}this.fire(d.type,d)},_defaultIconCreateFunction:function(d){var e=d.getChildCount();var f=" marker-cluster-";if(e<10){f+="small"}else{if(e<100){f+="medium"}else{f+="large"}}return new L.DivIcon({html:"<div><span>"+e+"</span></div>",className:"marker-cluster"+f,iconSize:new L.Point(40,40)})},_bindEvents:function(){var g=this._map,e=this.options.spiderfyOnMaxZoom,d=this.options.showCoverageOnHover,f=this.options.zoomToBoundsOnClick;if(e||f){this.on("clusterclick",this._zoomOrSpiderfy,this)}if(d){this.on("clustermouseover",this._showCoverage,this);this.on("clustermouseout",this._hideCoverage,this);g.on("zoomend",this._hideCoverage,this);g.on("layerremove",this._hideCoverageOnRemove,this)}},_zoomOrSpiderfy:function(f){var d=this._map;if(d.getMaxZoom()===d.getZoom()){if(this.options.spiderfyOnMaxZoom){f.layer.spiderfy()}}else{if(this.options.zoomToBoundsOnClick){f.layer.zoomToBounds()}}},_showCoverage:function(f){var d=this._map;if(this._inZoomAnimation){return}if(this._shownPolygon){d.removeLayer(this._shownPolygon)}if(f.layer.getChildCount()>2&&f.layer!==this._spiderfied){this._shownPolygon=new L.Polygon(f.layer.getConvexHull(),this.options.polygonOptions);d.addLayer(this._shownPolygon)}},_hideCoverage:function(){if(this._shownPolygon){this._map.removeLayer(this._shownPolygon);this._shownPolygon=null}},_hideCoverageOnRemove:function(d){if(d.layer===this){this._hideCoverage()}},_unbindEvents:function(){var e=this.options.spiderfyOnMaxZoom,d=this.options.showCoverageOnHover,g=this.options.zoomToBoundsOnClick,f=this._map;if(e||g){this.off("clusterclick",this._zoomOrSpiderfy,this)}if(d){this.off("clustermouseover",this._showCoverage,this);this.off("clustermouseout",this._hideCoverage,this);f.off("zoomend",this._hideCoverage,this);f.off("layerremove",this._hideCoverageOnRemove,this)}},_zoomEnd:function(){if(!this._map){return}this._mergeSplitClusters();this._zoom=this._map._zoom;this._currentShownBounds=this._getExpandedVisibleBounds()},_moveEnd:function(){if(this._inZoomAnimation){return}var d=this._getExpandedVisibleBounds();this._topClusterLevel._recursivelyRemoveChildrenFromMap(this._currentShownBounds,this._zoom,d);this._topClusterLevel._recursivelyAddChildrenToMap(null,this._zoom,d);this._currentShownBounds=d;return},_generateInitialClusters:function(){var e=this._map.getMaxZoom(),d=this.options.maxClusterRadius;if(this.options.disableClusteringAtZoom){e=this.options.disableClusteringAtZoom-1}this._maxZoom=e;this._gridClusters={};this._gridUnclustered={};for(var f=e;f>=0;f--){this._gridClusters[f]=new L.DistanceGrid(d);this._gridUnclustered[f]=new L.DistanceGrid(d)}this._topClusterLevel=new L.MarkerCluster(this,-1)},_addLayer:function(h,m){var f=this._gridClusters,d=this._gridUnclustered,l,i;if(this.options.singleMarkerMode){h.options.icon=this.options.iconCreateFunction({getChildCount:function(){return 1},getAllChildMarkers:function(){return[h]}})}for(;m>=0;m--){l=this._map.project(h.getLatLng(),m);var e=f[m].getNearObject(l);if(e){e._addChild(h);h.__parent=e;return}e=d[m].getNearObject(l);if(e){var j=e.__parent;if(j){this._removeLayer(e,false)}var k=new L.MarkerCluster(this,m,e,h);f[m].addObject(k,this._map.project(k._cLatLng,m));e.__parent=k;h.__parent=k;var g=k;for(i=m-1;i>j._zoom;i--){g=new L.MarkerCluster(this,i,g);f[i].addObject(g,this._map.project(e.getLatLng(),i))}j._addChild(g);for(i=m;i>=0;i--){if(!d[i].removeObject(e,this._map.project(e.getLatLng(),i))){break}}return}d[m].addObject(h,l)}this._topClusterLevel._addChild(h);h.__parent=this._topClusterLevel;return},_mergeSplitClusters:function(){if(this._zoom<this._map._zoom){this._animationStart();this._topClusterLevel._recursivelyRemoveChildrenFromMap(this._currentShownBounds,this._zoom,this._getExpandedVisibleBounds());this._animationZoomIn(this._zoom,this._map._zoom)}else{if(this._zoom>this._map._zoom){this._animationStart();this._animationZoomOut(this._zoom,this._map._zoom)}else{this._moveEnd()}}},_getExpandedVisibleBounds:function(){if(!this.options.removeOutsideVisibleBounds){return this.getBounds()}var i=this._map,e=i.getBounds(),d=e._southWest,h=e._northEast,g=L.Browser.mobile?0:Math.abs(d.lat-h.lat),f=L.Browser.mobile?0:Math.abs(d.lng-h.lng);return new L.LatLngBounds(new L.LatLng(d.lat-g,d.lng-f,true),new L.LatLng(h.lat+g,h.lng+f,true))},_animationAddLayerNonAnimated:function(d,e){if(e===d){this._featureGroup.addLayer(d)}else{if(e._childCount===2){e._addToMap();var f=e.getAllChildMarkers();this._featureGroup.removeLayer(f[0]);this._featureGroup.removeLayer(f[1])}else{e._updateIcon()}}}});L.MarkerClusterGroup.include(!L.DomUtil.TRANSITION?{_animationStart:function(){},_animationZoomIn:function(e,d){this._topClusterLevel._recursivelyRemoveChildrenFromMap(this._currentShownBounds,e);this._topClusterLevel._recursivelyAddChildrenToMap(null,d,this._getExpandedVisibleBounds())},_animationZoomOut:function(e,d){this._topClusterLevel._recursivelyRemoveChildrenFromMap(this._currentShownBounds,e);this._topClusterLevel._recursivelyAddChildrenToMap(null,d,this._getExpandedVisibleBounds())},_animationAddLayer:function(d,e){this._animationAddLayerNonAnimated(d,e)}}:{_animationStart:function(){this._map._mapPane.className+=" leaflet-cluster-anim";this._inZoomAnimation++},_animationEnd:function(){if(this._map){this._map._mapPane.className=this._map._mapPane.className.replace(" leaflet-cluster-anim","")}this._inZoomAnimation--;this.fire("animationend")},_animationZoomIn:function(j,f){var h=this,g=this._getExpandedVisibleBounds(),d=this._featureGroup,e;this._topClusterLevel._recursively(g,j,0,function(n){var k=n._latlng,l=n._markers,i;if(!g.contains(k)){k=null}if(n._isSingleParent()&&j+1===f){d.removeLayer(n);n._recursivelyAddChildrenToMap(null,f,g)}else{n.setOpacity(0);n._recursivelyAddChildrenToMap(k,f,g)}for(e=l.length-1;e>=0;e--){i=l[e];if(!g.contains(i._latlng)){d.removeLayer(i)}}});this._forceLayout();h._topClusterLevel._recursivelyBecomeVisible(g,f);d.eachLayer(function(i){if(!(i instanceof L.MarkerCluster)&&i._icon){i.setOpacity(1)}});h._topClusterLevel._recursively(g,j,f,function(i){i._recursivelyRestoreChildPositions(f)});setTimeout(function(){h._topClusterLevel._recursively(g,j,0,function(i){d.removeLayer(i);i.setOpacity(1)});h._animationEnd()},200)},_animationZoomOut:function(e,d){this._animationZoomOutSingle(this._topClusterLevel,e-1,d);this._topClusterLevel._recursivelyAddChildrenToMap(null,d,this._getExpandedVisibleBounds());this._topClusterLevel._recursivelyRemoveChildrenFromMap(this._currentShownBounds,e,this._getExpandedVisibleBounds())},_animationZoomOutSingle:function(d,h,e){var g=this._getExpandedVisibleBounds();d._recursivelyAnimateChildrenInAndAddSelfToMap(g,h+1,e);var f=this;this._forceLayout();d._recursivelyBecomeVisible(g,e);setTimeout(function(){if(d._childCount===1){var i=d._markers[0];i.setLatLng(i.getLatLng());i.setOpacity(1)}else{d._recursively(g,e,0,function(j){j._recursivelyRemoveChildrenFromMap(g,h+1)})}f._animationEnd()},200)},_animationAddLayer:function(e,g){var f=this,d=this._featureGroup;d.addLayer(e);if(g!==e){if(g._childCount>2){g._updateIcon();this._forceLayout();this._animationStart();e._setPos(this._map.latLngToLayerPoint(g.getLatLng()));e.setOpacity(0);setTimeout(function(){d.removeLayer(e);e.setOpacity(1);f._animationEnd()},200)}else{this._forceLayout();f._animationStart();f._animationZoomOutSingle(g,this._map.getMaxZoom(),this._map.getZoom())}}},_forceLayout:function(){L.Util.falseFn(a.body.offsetWidth)}});L.markerClusterGroup=function(d){return new L.MarkerClusterGroup(d)};L.MarkerCluster=L.Marker.extend({initialize:function(g,f,e,d){L.Marker.prototype.initialize.call(this,e?(e._cLatLng||e.getLatLng()):new L.LatLng(0,0),{icon:this});this._group=g;this._zoom=f;this._markers=[];this._childClusters=[];this._childCount=0;this._iconNeedsUpdate=true;this._bounds=new L.LatLngBounds();if(e){this._addChild(e)}if(d){this._addChild(d)}},getAllChildMarkers:function(f){f=f||[];for(var e=this._childClusters.length-1;e>=0;e--){this._childClusters[e].getAllChildMarkers(f)}for(var d=this._markers.length-1;d>=0;d--){f.push(this._markers[d])}return f},getChildCount:function(){return this._childCount},zoomToBounds:function(){this._group._map.fitBounds(this._bounds)},getBounds:function(){var d=new L.LatLngBounds();d.extend(this._bounds);return d},_updateIcon:function(){this._iconNeedsUpdate=true;if(this._icon){this.setIcon(this)}},createIcon:function(){if(this._iconNeedsUpdate){this._iconObj=this._group.options.iconCreateFunction(this);this._iconNeedsUpdate=false}return this._iconObj.createIcon()},createShadow:function(){return this._iconObj.createShadow()},_addChild:function(d,e){this._iconNeedsUpdate=true;this._expandBounds(d);if(d instanceof L.MarkerCluster){if(!e){this._childClusters.push(d);d.__parent=this}this._childCount+=d._childCount}else{if(!e){this._markers.push(d)}this._childCount++}if(this.__parent){this.__parent._addChild(d,true)}},_expandBounds:function(f){var g,e=f._wLatLng||f._latlng;if(f instanceof L.MarkerCluster){this._bounds.extend(f._bounds);g=f._childCount}else{this._bounds.extend(e);g=1}if(!this._cLatLng){this._cLatLng=f._cLatLng||e}var d=this._childCount+g;if(!this._wLatLng){this._latlng=this._wLatLng=new L.LatLng(e.lat,e.lng)}else{this._wLatLng.lat=(e.lat*g+this._wLatLng.lat*this._childCount)/d;this._wLatLng.lng=(e.lng*g+this._wLatLng.lng*this._childCount)/d}},_addToMap:function(d){if(d){this._backupLatlng=this._latlng;this.setLatLng(d)}this._group._featureGroup.addLayer(this)},_recursivelyAnimateChildrenIn:function(f,d,e){this._recursively(f,0,e-1,function(k){var j=k._markers,h,g;for(h=j.length-1;h>=0;h--){g=j[h];if(g._icon){g._setPos(d);g.setOpacity(0)}}},function(k){var i=k._childClusters,h,g;for(h=i.length-1;h>=0;h--){g=i[h];if(g._icon){g._setPos(d);g.setOpacity(0)}}})},_recursivelyAnimateChildrenInAndAddSelfToMap:function(f,e,d){this._recursively(f,d,0,function(g){g._recursivelyAnimateChildrenIn(f,g._group._map.latLngToLayerPoint(g.getLatLng()).round(),e);if(g._isSingleParent()&&e-1===d){g.setOpacity(1);g._recursivelyRemoveChildrenFromMap(f,e)}else{g.setOpacity(0)}g._addToMap()})},_recursivelyBecomeVisible:function(d,e){this._recursively(d,0,e,null,function(f){f.setOpacity(1)})},_recursivelyAddChildrenToMap:function(d,f,e){this._recursively(e,-1,f,function(j){if(f===j._zoom){return}for(var h=j._markers.length-1;h>=0;h--){var g=j._markers[h];if(!e.contains(g._latlng)){continue}if(d){g._backupLatlng=g.getLatLng();g.setLatLng(d);if(g.setOpacity){g.setOpacity(0)}}j._group._featureGroup.addLayer(g)}},function(g){g._addToMap(d)})},_recursivelyRestoreChildPositions:function(h){for(var g=this._markers.length-1;g>=0;g--){var d=this._markers[g];if(d._backupLatlng){d.setLatLng(d._backupLatlng);delete d._backupLatlng}}if(h-1===this._zoom){for(var f=this._childClusters.length-1;f>=0;f--){this._childClusters[f]._restorePosition()}}else{for(var e=this._childClusters.length-1;e>=0;e--){this._childClusters[e]._recursivelyRestoreChildPositions(h)}}},_restorePosition:function(){if(this._backupLatlng){this.setLatLng(this._backupLatlng);delete this._backupLatlng}},_recursivelyRemoveChildrenFromMap:function(e,h,g){var d,f;this._recursively(e,-1,h-1,function(i){for(f=i._markers.length-1;f>=0;f--){d=i._markers[f];if(!g||!g.contains(d._latlng)){i._group._featureGroup.removeLayer(d);if(d.setOpacity){d.setOpacity(1)}}}},function(i){for(f=i._childClusters.length-1;f>=0;f--){d=i._childClusters[f];if(!g||!g.contains(d._latlng)){i._group._featureGroup.removeLayer(d);if(d.setOpacity){d.setOpacity(1)}}}})},_recursively:function(k,j,f,d,g){var h=this._childClusters,m=this._zoom,e,l;if(j>m){for(e=h.length-1;e>=0;e--){l=h[e];if(k.intersects(l._bounds)){l._recursively(k,j,f,d,g)}}}else{if(d){d(this)}if(g&&this._zoom===f){g(this)}if(f>m){for(e=h.length-1;e>=0;e--){l=h[e];if(k.intersects(l._bounds)){l._recursively(k,j,f,d,g)}}}}},_recalculateBounds:function(){var f=this._markers,e=this._childClusters,d;this._bounds=new L.LatLngBounds();delete this._wLatLng;for(d=f.length-1;d>=0;d--){this._expandBounds(f[d])}for(d=e.length-1;d>=0;d--){this._expandBounds(e[d])}},_isSingleParent:function(){return this._childClusters.length>0&&this._childClusters[0]._childCount===this._childCount}});L.DistanceGrid=function(d){this._cellSize=d;this._sqCellSize=d*d;this._grid={};this._objectPoint={}};L.DistanceGrid.prototype={addObject:function(i,f){var e=this._getCoord(f.x),k=this._getCoord(f.y),h=this._grid,j=h[k]=h[k]||{},d=j[e]=j[e]||[],g=L.Util.stamp(i);this._objectPoint[g]=f;d.push(i)},updateObject:function(e,d){this.removeObject(e);this.addObject(e,d)},removeObject:function(e,k){var j=this._getCoord(k.x),h=this._getCoord(k.y),d=this._grid,m=d[h]=d[h]||{},l=m[j]=m[j]||[],f,g;delete this._objectPoint[L.Util.stamp(e)];for(f=0,g=l.length;f<g;f++){if(l[f]===e){l.splice(f,1);if(g===1){delete m[j]}return true}}},eachObject:function(n,e){var h,g,f,m,p,o,l,d=this._grid;for(h in d){p=d[h];for(g in p){o=p[g];for(f=0,m=o.length;f<m;f++){l=n.call(e,o[f]);if(l){f--;m--}}}}},getNearObject:function(q){var p=this._getCoord(q.x),o=this._getCoord(q.y),h,f,e,t,s,l,g,n,r=this._objectPoint,m=this._sqCellSize,d=null;for(h=o-1;h<=o+1;h++){t=this._grid[h];if(t){for(f=p-1;f<=p+1;f++){s=t[f];if(s){for(e=0,l=s.length;e<l;e++){g=s[e];n=this._sqDist(r[L.Util.stamp(g)],q);if(n<m){m=n;d=g}}}}}}return d},_getCoord:function(d){return Math.floor(d/this._cellSize)},_sqDist:function(g,f){var e=f.x-g.x,d=f.y-g.y;return e*e+d*d}};(function(){L.QuickHull={getDistant:function(f,g){var d=g[1].lat-g[0].lat,e=g[0].lng-g[1].lng;return(e*(f.lat-g[0].lat)+d*(f.lng-g[0].lng))},findMostDistantPointFromBaseLine:function(f,l){var j=0,e=null,h=[],g,k,m;for(g=l.length-1;g>=0;g--){k=l[g];m=this.getDistant(k,f);if(m>0){h.push(k)}else{continue}if(m>j){j=m;e=k}}return{maxPoint:e,newPoints:h}},buildConvexHull:function(d,g){var f=[],e=this.findMostDistantPointFromBaseLine(d,g);if(e.maxPoint){f=f.concat(this.buildConvexHull([d[0],e.maxPoint],e.newPoints));f=f.concat(this.buildConvexHull([e.maxPoint,d[1]],e.newPoints));return f}else{return[d]}},getConvexHull:function(j){var k=false,l=false,d=null,h=null,e;for(e=j.length-1;e>=0;e--){var g=j[e];if(k===false||g.lat>k){d=g;k=g.lat}if(l===false||g.lat<l){h=g;l=g.lat}}var f=[].concat(this.buildConvexHull([h,d],j),this.buildConvexHull([d,h],j));return f}}}());L.MarkerCluster.include({getConvexHull:function(){var g=this.getAllChildMarkers(),e=[],j=[],h,f,d;for(d=g.length-1;d>=0;d--){f=g[d].getLatLng();e.push(f)}h=L.QuickHull.getConvexHull(e);for(d=h.length-1;d>=0;d--){j.push(h[d][0])}return j}});L.MarkerCluster.include({_2PI:Math.PI*2,_circleFootSeparation:25,_circleStartAngle:Math.PI/6,_spiralFootSeparation:28,_spiralLengthStart:11,_spiralLengthFactor:5,_circleSpiralSwitchover:9,spiderfy:function(){if(this._group._spiderfied===this||this._group._inZoomAnimation){return}var h=this.getAllChildMarkers(),g=this._group,f=g._map,d=f.latLngToLayerPoint(this._latlng),e;this._group._unspiderfy();this._group._spiderfied=this;if(h.length>=this._circleSpiralSwitchover){e=this._generatePointsSpiral(h.length,d)}else{d.y+=10;e=this._generatePointsCircle(h.length,d)}this._animationSpiderfy(h,e)},unspiderfy:function(d){if(this._group._inZoomAnimation){return}this._animationUnspiderfy(d);this._group._spiderfied=null},_generatePointsCircle:function(h,l){var d=this._group.options.spiderfyDistanceMultiplier*this._circleFootSeparation*(2+h),j=d/this._2PI,g=this._2PI/h,f=[],e,k;f.length=h;for(e=h-1;e>=0;e--){k=this._circleStartAngle+e*g;f[e]=new L.Point(l.x+j*Math.cos(k),l.y+j*Math.sin(k))._round()}return f},_generatePointsSpiral:function(g,l){var h=this._group.options.spiderfyDistanceMultiplier*this._spiralLengthStart,f=this._group.options.spiderfyDistanceMultiplier*this._spiralFootSeparation,j=this._group.options.spiderfyDistanceMultiplier*this._spiralLengthFactor,k=0,e=[],d;e.length=g;for(d=g-1;d>=0;d--){k+=f/h+d*0.0005;e[d]=new L.Point(l.x+h*Math.cos(k),l.y+h*Math.sin(k))._round();h+=this._2PI*j/k}return e},_noanimationUnspiderfy:function(){var j=this._group,h=j._map,e=j._featureGroup,g=this.getAllChildMarkers(),d,f;this.setOpacity(1);for(f=g.length-1;f>=0;f--){d=g[f];e.removeLayer(d);if(d._preSpiderfyLatlng){d.setLatLng(d._preSpiderfyLatlng);delete d._preSpiderfyLatlng}if(d.setZIndexOffset){d.setZIndexOffset(0)}if(d._spiderLeg){h.removeLayer(d._spiderLeg);delete d._spiderLeg}}}});L.MarkerCluster.include(!L.DomUtil.TRANSITION?{_animationSpiderfy:function(f,j){var l=this._group,e=l._map,d=l._featureGroup,h,g,n,k;for(h=f.length-1;h>=0;h--){k=e.layerPointToLatLng(j[h]);g=f[h];g._preSpiderfyLatlng=g._latlng;g.setLatLng(k);if(g.setZIndexOffset){g.setZIndexOffset(1000000)}d.addLayer(g);n=new L.Polyline([this._latlng,k],{weight:1.5,color:"#222"});e.addLayer(n);g._spiderLeg=n}this.setOpacity(0.3);l.fire("spiderfied")},_animationUnspiderfy:function(){this._noanimationUnspiderfy()}}:{SVG_ANIMATION:(function(){return a.createElementNS("http://www.w3.org/2000/svg","animate").toString().indexOf("SVGAnimate")>-1}()),_animationSpiderfy:function(j,o){var r=this,t=this._group,f=t._map,d=t._featureGroup,p=f.latLngToLayerPoint(this._latlng),n,h,s,q;for(n=j.length-1;n>=0;n--){h=j[n];if(h.setOpacity){h.setZIndexOffset(1000000);h.setOpacity(0);d.addLayer(h);h._setPos(p)}else{d.addLayer(h)}}t._forceLayout();t._animationStart();var e=L.Path.SVG?0:0.3,l=L.Path.SVG_NS;for(n=j.length-1;n>=0;n--){q=f.layerPointToLatLng(o[n]);h=j[n];h._preSpiderfyLatlng=h._latlng;h.setLatLng(q);if(h.setOpacity){h.setOpacity(1)}s=new L.Polyline([r._latlng,q],{weight:1.5,color:"#222",opacity:e});f.addLayer(s);h._spiderLeg=s;if(!L.Path.SVG||!this.SVG_ANIMATION){continue}var g=s._path.getTotalLength();s._path.setAttribute("stroke-dasharray",g+","+g);var k=a.createElementNS(l,"animate");k.setAttribute("attributeName","stroke-dashoffset");k.setAttribute("begin","indefinite");k.setAttribute("from",g);k.setAttribute("to",0);k.setAttribute("dur",0.25);s._path.appendChild(k);k.beginElement();k=a.createElementNS(l,"animate");k.setAttribute("attributeName","stroke-opacity");k.setAttribute("attributeName","stroke-opacity");k.setAttribute("begin","indefinite");k.setAttribute("from",0);k.setAttribute("to",0.5);k.setAttribute("dur",0.25);s._path.appendChild(k);k.beginElement()}r.setOpacity(0.3);if(L.Path.SVG){this._group._forceLayout();for(n=j.length-1;n>=0;n--){h=j[n]._spiderLeg;h.options.opacity=0.5;h._path.setAttribute("stroke-opacity",0.5)}}setTimeout(function(){t._animationEnd();t.fire("spiderfied")},200)},_animationUnspiderfy:function(o){var n=this._group,e=n._map,d=n._featureGroup,k=o?e._latLngToNewLayerPoint(this._latlng,o.zoom,o.center):e.latLngToLayerPoint(this._latlng),f=this.getAllChildMarkers(),j=L.Path.SVG&&this.SVG_ANIMATION,g,h,l;n._animationStart();this.setOpacity(1);for(h=f.length-1;h>=0;h--){g=f[h];if(!g._preSpiderfyLatlng){continue}g.setLatLng(g._preSpiderfyLatlng);delete g._preSpiderfyLatlng;if(g.setOpacity){g._setPos(k);g.setOpacity(0)}else{d.removeLayer(g)}if(j){l=g._spiderLeg._path.childNodes[0];l.setAttribute("to",l.getAttribute("from"));l.setAttribute("from",0);l.beginElement();l=g._spiderLeg._path.childNodes[1];l.setAttribute("from",0.5);l.setAttribute("to",0);l.setAttribute("stroke-opacity",0);l.beginElement();g._spiderLeg._path.setAttribute("stroke-opacity",0)}}setTimeout(function(){var i=0;for(h=f.length-1;h>=0;h--){g=f[h];if(g._spiderLeg){i++}}for(h=f.length-1;h>=0;h--){g=f[h];if(!g._spiderLeg){continue}if(g.setOpacity){g.setOpacity(1);g.setZIndexOffset(0)}if(i>1){d.removeLayer(g)}e.removeLayer(g._spiderLeg);delete g._spiderLeg}n._animationEnd()},200)}});L.MarkerClusterGroup.include({_spiderfied:null,_spiderfierOnAdd:function(){this._map.on("click",this._unspiderfyWrapper,this);if(this._map.options.zoomAnimation){this._map.on("zoomstart",this._unspiderfyZoomStart,this)}else{this._map.on("zoomend",this._unspiderfyWrapper,this)}if(L.Path.SVG&&!L.Browser.touch){this._map._initPathRoot()}},_spiderfierOnRemove:function(){this._map.off("click",this._unspiderfyWrapper,this);this._map.off("zoomstart",this._unspiderfyZoomStart,this);this._map.off("zoomanim",this._unspiderfyZoomAnim,this);this._unspiderfy()},_unspiderfyZoomStart:function(){if(!this._map){return}this._map.on("zoomanim",this._unspiderfyZoomAnim,this)},_unspiderfyZoomAnim:function(d){if(L.DomUtil.hasClass(this._map._mapPane,"leaflet-touching")){return}this._map.off("zoomanim",this._unspiderfyZoomAnim,this);this._unspiderfy(d)},_unspiderfyWrapper:function(){this._unspiderfy()},_unspiderfy:function(d){if(this._spiderfied){this._spiderfied.unspiderfy(d)}},_noanimationUnspiderfy:function(){if(this._spiderfied){this._spiderfied._noanimationUnspiderfy()}},_unspiderfyLayer:function(d){if(d._spiderLeg){this._featureGroup.removeLayer(d);d.setOpacity(1);d.setZIndexOffset(0);this._map.removeLayer(d._spiderLeg);delete d._spiderLeg}}})}(window,document));L.Control.Zoomslider=(function(){var b=L.Draggable.extend({initialize:function(d,e,c){L.Draggable.prototype.initialize.call(this,d,d);this._element=d;this._stepHeight=e;this._knobHeight=c;this.on("predrag",function(){this._newPos.x=0;this._newPos.y=this._adjust(this._newPos.y)},this)},_adjust:function(d){var c=Math.round(this._toValue(d));c=Math.max(0,Math.min(this._maxValue,c));return this._toY(c)},_toY:function(c){return this._k*c+this._m},_toValue:function(c){return(c-this._m)/this._k},setSteps:function(c){var d=c*this._stepHeight;this._maxValue=c-1;this._k=-this._stepHeight;this._m=d-(this._stepHeight+this._knobHeight)/2},setPosition:function(c){L.DomUtil.setPosition(this._element,L.point(0,this._adjust(c)))},setValue:function(c){this.setPosition(this._toY(c))},getValue:function(){return this._toValue(L.DomUtil.getPosition(this._element).y)}});var a=L.Control.extend({options:{position:"topleft",stepHeight:8,knobHeight:6,styleNS:"leaflet-control-zoomslider"},onAdd:function(c){this._map=c;this._ui=this._createUI();this._knob=new b(this._ui.knob,this.options.stepHeight,this.options.knobHeight);c.whenReady(this._initKnob,this).whenReady(this._initEvents,this).whenReady(this._updateSize,this).whenReady(this._updateKnobValue,this).whenReady(this._updateDisabled,this);return this._ui.bar},onRemove:function(c){c.off("zoomlevelschange",this._updateSize,this).off("zoomend zoomlevelschange",this._updateKnobValue,this).off("zoomend zoomlevelschange",this._updateDisabled,this)},_createUI:function(){var d={},c=this.options.styleNS;d.bar=L.DomUtil.create("div",c+" leaflet-bar"),d.zoomIn=this._createZoomBtn("in","top",d.bar),d.wrap=L.DomUtil.create("div",c+"-wrap leaflet-bar-part",d.bar),d.zoomOut=this._createZoomBtn("out","bottom",d.bar),d.body=L.DomUtil.create("div",c+"-body",d.wrap),d.knob=L.DomUtil.create("div",c+"-knob");L.DomEvent.disableClickPropagation(d.bar);L.DomEvent.disableClickPropagation(d.knob);return d},_createZoomBtn:function(e,d,c){var f=this.options.styleNS+"-"+e+" leaflet-bar-part leaflet-bar-part-"+d,g=L.DomUtil.create("a",f,c);g.href="#";g.title="Zoom "+e;L.DomEvent.on(g,"click",L.DomEvent.preventDefault);return g},_initKnob:function(){this._knob.enable();this._ui.body.appendChild(this._ui.knob)},_initEvents:function(c){this._map.on("zoomlevelschange",this._updateSize,this).on("zoomend zoomlevelschange",this._updateKnobValue,this).on("zoomend zoomlevelschange",this._updateDisabled,this);L.DomEvent.on(this._ui.body,"click",this._onSliderClick,this);L.DomEvent.on(this._ui.zoomIn,"click",this._zoomIn,this);L.DomEvent.on(this._ui.zoomOut,"click",this._zoomOut,this);this._knob.on("dragend",this._updateMapZoom,this)},_onSliderClick:function(c){var d=(c.touches&&c.touches.length===1?c.touches[0]:c),f=L.DomEvent.getMousePosition(d,this._ui.body).y;this._knob.setPosition(f);this._updateMapZoom()},_zoomIn:function(c){this._map.zoomIn(c.shiftKey?3:1)},_zoomOut:function(c){this._map.zoomOut(c.shiftKey?3:1)},_zoomLevels:function(){var c=this._map.getMaxZoom()-this._map.getMinZoom()+1;return c<Infinity?c:0},_toZoomLevel:function(c){return c+this._map.getMinZoom()},_toValue:function(c){return c-this._map.getMinZoom()},_updateSize:function(){var c=this._zoomLevels();this._ui.body.style.height=this.options.stepHeight*c+"px";this._knob.setSteps(c)},_updateMapZoom:function(){this._map.setZoom(this._toZoomLevel(this._knob.getValue()))},_updateKnobValue:function(){this._knob.setValue(this._toValue(this._map.getZoom()))},_updateDisabled:function(){var d=this._map.getZoom(),c=this.options.styleNS+"-disabled";L.DomUtil.removeClass(this._ui.zoomIn,c);L.DomUtil.removeClass(this._ui.zoomOut,c);if(d===this._map.getMinZoom()){L.DomUtil.addClass(this._ui.zoomOut,c)}if(d===this._map.getMaxZoom()){L.DomUtil.addClass(this._ui.zoomIn,c)}}});return a})();L.Map.mergeOptions({zoomControl:false,zoomsliderControl:true});L.Map.addInitHook(function(){if(this.options.zoomsliderControl){this.zoomsliderControl=new L.Control.Zoomslider();this.addControl(this.zoomsliderControl)}});L.control.zoomslider=function(a){return new L.Control.Zoomslider(a)};L.Control.Pan=L.Control.extend({options:{position:"topleft",panOffset:500},onAdd:function(c){var b="leaflet-control-pan",a=L.DomUtil.create("div",b),d=this.options.panOffset;this._panButton("Up",b+"-up",a,c,new L.Point(0,-d));this._panButton("Left",b+"-left",a,c,new L.Point(-d,0));this._panButton("Right",b+"-right",a,c,new L.Point(d,0));this._panButton("Down",b+"-down",a,c,new L.Point(0,d));return a},_panButton:function(g,b,a,d,f,e){var h=L.DomUtil.create("div",b+"-wrap",a);var c=L.DomUtil.create("a",b,h);c.href="#";c.title=g;L.DomEvent.on(c,"click",L.DomEvent.stopPropagation).on(c,"click",L.DomEvent.preventDefault).on(c,"click",function(){d.panBy(f)},d).on(c,"dblclick",L.DomEvent.stopPropagation);return c}});L.Map.mergeOptions({panControl:true});L.Map.addInitHook(function(){if(this.options.panControl){this.panControl=new L.Control.Pan();this.addControl(this.panControl)}});L.control.pan=function(a){return new L.Control.Pan(a)};