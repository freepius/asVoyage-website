anarchos semitas - voyage : the website
=======================================

About
=====

Requirements
------------

- Any flavor of PHP 5.4 or above should do
- PHP extension : Fileinfo, GD, Exif
- [optional] PHPUnit 3.5+ to execute the test suite (phpunit --version)

Used libraries
--------------

### Server side

* [Silex](http://silex.sensiolabs.org/) : PHP micro-framework based on the Symfony2 Components
* [Twig](http://twig.sensiolabs.org/) : template engine for PHP
* [Swiftmailer](http://swiftmailer.org/) : PHP mailer
* [php-markdown](https://michelf.ca/projects/php-markdown/) : PHP Markdown parser
* [php-smartypants](https://michelf.ca/projects/php-smartypants/) : PHP implementation of SmartyPants

For more, see *composer.json* file.

### Client side

* [Bootstrap](http://twitter.github.io/bootstrap/) : front-end web framework
* [Font-Awesome](http://fortawesome.github.io/Font-Awesome/) : iconic font designed for Bootstrap
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


BUG
===

* BUG related to Http cache : on production server, switch locale doesn't change the locale (without to do a Ctrl+F5).
  See the diff in Http cache config for Apache2.
* BUG on IE 9 : problem to refresh captcha (eg: on contact page)
* BUG on IE 8 : very bad displaying of pages (html5 not recognized ?)


TODO
====

* Home page :
  * Add a block summarizing the news of the last 7 days
* Blog :
  * Dashboard -> add the date of the last comment
  * Creation/Updating -> Add a "help box" for RichText
  * Reading -> Add a box containing related articles/contents
  * Write RichText macros to easily include a media
* Make RSS
* Make a translation table for tags
* Add a test suite (unit and functional) !
* Main map : multiple tile layers (only one is displayed depending on zoom)
  http://moonlite.github.io/Leaflet.MultiTileLayer/
* Main map : possibility to "fullscreen" the map
* Register :
  * make a real creation/updating page
  * make a page to overview the last register entries

For production
--------------

* HttpCache:
  * Blog articles => cache the generated HTML from RichText
* Improve the inclusion of JS and CSS files (if not using => do not include) ?
* Use https certificate for admin pages

Low priority
------------

* Use DataTable jquery plugin to Blog dashboard ?
* Transform App\Util\RichText to Silex provider ?
* Locale depending on :
  * user preferences (the 'Accept-Languages' HTTP header)
  * geo-localization
