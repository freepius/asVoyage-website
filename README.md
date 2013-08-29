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
  * [Lightbox 2](http://lokeshdhakar.com/projects/lightbox2/)
  * [jQuery File Upload](http://blueimp.github.io/jQuery-File-Upload/) : file upload widget
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
* On Blog "reading" page : add box containing related articles/contents
* Contact : log when a message is sent
* Carto : improve !!!
* Traduction of new base pages (about, our-trips, etc.)
* Revoir le CSS : les marges pour les titres, le centrage dans about, l'affichage sur grand et petit écran, etc.
* Media -> better set meta actions (+/- tags ; +/- hours...)
* Make RSS or newsletter
* BUG (not critic) : in DEBUG mode => when Logout => error (related to Session and header already sent)
* Follow my pullrequest for Lightbox2.

For production
--------------

* Solve the TODOs
* Update vendor and libraries
* Index some Mongo fields ?
* Cache (with APC/Memcached ?) :
  * Blog repository => caching for listTags() and countArticlesByYearMonth()
  * Blog controller => caching the generated HTML from RichText
* Minified JS and CSS files
* Contact : make secure the message sending (with TLS)
* Improve accessibility of pages !

Low priority
------------

* Use DataTable jquery plugin to Blog dashboard ?
* Transform App\Util\RichText to Silex provider ?
* Locale depending on geo-localization
