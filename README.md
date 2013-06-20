anarchos semitas - voyage : the website
=======================================

About
=====

Requirements
------------

- Any flavor of PHP 5.3 or above should do
- PHP extension : Intl, Fileinfo, ZIP, GD
- [optional] PHPUnit 3.5+ to execute the test suite (phpunit --version)

External libraries used
-----------------------

* [KCFINDER](http://kcfinder.sunhater.com/) as Web file manager
* [Bootstrap](http://twitter.github.io/bootstrap/)
* [Modernizr](http://modernizr.com/)
* [jQuery 2](http://jquery.com/) and plugins :
  * [Lightbox 2](https://github.com/javierjulio/lightbox2) : an advanced fork of [this](http://lokeshdhakar.com/projects/lightbox2/)
  * [Elastic](http://unwrongest.com/projects/elastic/) : auto grows textareas

Author
------

Mathieu Poisbeau - <freepius44@gmail.com>

License
-------

*anarchos semitas - voyage* is licensed under the **CC0 1.0 Universal** -- see the `LICENSE` file for details.


TODO
====

* Blog reading page : when creating/updating a comment => keep the filters / HTTP\_REFERER
* Blog creating/updating page :
  * Add help for Markdown
* Configure and make secure KCFinder
* Improve the inclusion of JS and CSS files (if not using => do not include)
* Reorganize HTML5 => make semantic !
* On Blog "reading" page : add box containing related articles/contents

For production
--------------

* Set properly the BlogController::LIMIT\_ARTICLES constant (eg: to 10)
* Solve the TODOs
* Update vendor and libraries
* Change name and password for admin
* Management of errors and exceptions
* Index some Mongo fields ?
* Cache (with APC/Memcached ?) :
  * Blog repository => caching for listTags() and countArticlesByYearMonth()
  * Blog controller => caching the generated HTML from Markdown
* Minified JS and CSS files

Low priority
------------

* Use DataTable jquery plugin to Blog dashboard ?
* Transform App\Util\MarkdownTypo to Silex provider
* Locale depending on geo-localization
* Migrate to PHP 5.4 (or better to PHP 5.5) : particulary, use Trait for \Silex\Application
