anarchos semitas - voyage : the website
=======================================

About
=====

Requirements
------------

- Any flavor of PHP 5.4 or above should do
- PHP extension : Fileinfo, GD, Exif
- [optional] PHPUnit 3.5+ to execute the test suite (phpunit --version)

External libraries used
-----------------------

* [Bootstrap](http://twitter.github.io/bootstrap/) : front-end web framework
* [Font-Awesome](http://fortawesome.github.io/Font-Awesome/) : iconic font designed for Bootstrap
* [Modernizr](http://modernizr.com/)
* [jQuery](http://jquery.com/) and plugins :
  * [Lightbox 2](http://lokeshdhakar.com/projects/lightbox2/)
  * [jQuery File Upload](http://blueimp.github.io/jQuery-File-Upload/) : file upload widget
  * [Elastic](http://unwrongest.com/projects/elastic/) : auto grows textareas
  * [ShiftCheckbox](https://github.com/nylen/shiftcheckbox) : handle a "select all" widget + selecting ranges of checkboxes via Shift+Click
* [Leaflet](http://leafletjs.com/) and plugins :
  * [AwesomeMarkers](https://github.com/lvoogdt/Leaflet.awesome-markers)
  * [OverlappingMarkerSpiderfier](https://github.com/jawj/OverlappingMarkerSpiderfier-Leaflet)
  * [MarkerCluster](https://github.com/Leaflet/Leaflet.markercluster)
  * [ZoomSlider](https://github.com/kartena/Leaflet.zoomslider)
  * [PanControl](https://github.com/kartena/Leaflet.Pancontrol)
  * [Label](https://github.com/Leaflet/Leaflet.label)

Author
------

Mathieu Poisbeau - <freepius44@gmail.com>

License
-------

*anarchos semitas - voyage* is licensed under the **CC0 1.0 Universal** -- see the `LICENSE` file for details.


TODO
====

* Blog creating/updating page :
  * Add help for RichText
* On Blog "reading" page : add box containing related articles/contents
* Make RSS
* BUG (not critic) : in DEBUG mode => when Logout => error (related to Session and header already sent)
* Make a translation table for tags
* Blog : macro to include a media in a blog article
* BUG on IE 9 : problem to refresh captcha (eg: on contact page)
* Improve tags size on tags box ?!
* Home page : insert the travel path on mini-map
* Media module : add an "info box" on home page
* Follow my pullrequest for :
  * Lightbox2               (I make the local change)
  * Leaflet Zoom Control    (I make the local change)
  * Symfony/HttpKernel      (I made the local change)

For production
--------------

* Solve the TODOs
* Update vendor and libraries
* HttpCache:
  * Blog articles => cache the generated HTML from RichText
* Minified JS and CSS files
* Improve the inclusion of JS and CSS files (if not using => do not include)

Low priority
------------

* Use DataTable jquery plugin to Blog dashboard ?
* Transform App\Util\RichText to Silex provider ?
* Locale depending on :
  * user preferences (the 'Accept-Languages' HTTP header)
  * geo-localization
