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
* Traduction of new base pages (about, our-trips, etc.)
* Revoir le CSS : les marges pour les titres, le centrage dans about, l'affichage sur grand et petit écran, etc.
* Make RSS or newsletter
* BUG (not critic) : in DEBUG mode => when Logout => error (related to Session and header already sent)
* Follow my pullrequest for Lightbox2.
* Follow my pullrequest for Leaflet Zoom Control.
* Make a translation table for tags
* Blog : macro to include a media in a blog article
* New hom page
* BUG on IE 9 : problem to refresh captcha (eg: on contact page)
* Improve tags size on tags box ?!
* Media module : add an "info box" on home page

For production
--------------

* Solve the TODOs
* Update vendor and libraries
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
