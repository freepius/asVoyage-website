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


BUG
===

* BUG : Media/Blog -> filter on December return empty list
* BUG on IE 9 : problem to refresh captcha (eg: on contact page)
* BUG on IE 8 : very bad displaying of pages (html5 not recognized ?)


TODO
====

* Blog :
  * Creation/Updating -> Add a "help box" for RichText
  * Reading -> Add a box containing related articles/contents
  * Write RichText macros to easily include a media
* Blog/Media : Improve tags size on tags box
* Make RSS
* Make a translation table for tags
* Follow my pullrequest for :
  * Lightbox2               (I make the local change)
  * Leaflet Zoom Control    (I make the local change)
* Add a test suite (unit and functional) !

For production
--------------

* Update vendor and libraries
* HttpCache:
  * Blog articles => cache the generated HTML from RichText
* Improve the inclusion of JS and CSS files (if not using => do not include)

Low priority
------------

* Use DataTable jquery plugin to Blog dashboard ?
* Transform App\Util\RichText to Silex provider ?
* Locale depending on :
  * user preferences (the 'Accept-Languages' HTTP header)
  * geo-localization
