anarchos semitas - voyage : the website
=======================================

About
=====

Requirements
------------

- Any flavor of PHP 5.4 or above should do
- PHP extension : Fileinfo, GD
- [optional] PHPUnit 3.5+ to execute the test suite (phpunit --version)

External libraries used
-----------------------

* [Bootstrap](http://twitter.github.io/bootstrap/) : front-end web framework
* [Font-Awesome](http://fortawesome.github.io/Font-Awesome/) : iconic font designed for Bootstrap
* [Modernizr](http://modernizr.com/)
* [jQuery](http://jquery.com/) and plugins :
  * [Lightbox 2](https://github.com/javierjulio/lightbox2) : an advanced fork of [this](http://lokeshdhakar.com/projects/lightbox2/)
  * [Elastic](http://unwrongest.com/projects/elastic/) : auto grows textareas
* [Leaflet](http://leafletjs.com/) and plugins :
  * [awesome-markers plugin](https://github.com/lvoogdt/Leaflet.awesome-markers)

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
* Improve the inclusion of JS and CSS files (if not using => do not include)
* Reorganize HTML5 => make semantic !
* On Blog "reading" page : add box containing related articles/contents
* STYLE : change some "em sizes" to "px sizes" (for style consistency) !
* Contact : log when a message is sent
* Contact : make secure the message sending (with TLS)
* Set RichText to write HTML5 (and not XHTML) !
* Carto : conflict between Leaflet and Bootstrap styles
* Traduction of new base pages (about, our-trips, etc.)

For production
--------------

* Make a git patch for PROD configs : password, swiftmailer config, mongodb config, etc.
* Solve the TODOs
* Update vendor and libraries
* Management of errors and exceptions
* Index some Mongo fields ?
* Cache (with APC/Memcached ?) :
  * Blog repository => caching for listTags() and countArticlesByYearMonth()
  * Blog controller => caching the generated HTML from RichText
* Minified JS and CSS files

Low priority
------------

* Use DataTable jquery plugin to Blog dashboard ?
* Transform App\Util\RichText to Silex provider ?
* Locale depending on geo-localization
