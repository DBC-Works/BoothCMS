CHANGELOG
=========

Version 0.5.0(2019-08-13)
-------------------------

* Add `ReleaseTime` header support(#15)
    * The content can't be accessed when the date and time of this header is in the future
* Correct structured data template
* Correct document typo
* Change testing framework from [pear(`run-tests`)](https://pear.php.net/manual/en/guide.users.commandline.commands.php) to [PHPUnit](https://phpunit.readthedocs.io/ja/latest/#)

Version 0.4.3(2019-04-20)
-------------------------

* Add '[Video](https://developers.google.com/search/docs/data-types/video)' structured data template
* Improve '[Review](https://developers.google.com/search/docs/data-types/review-snippet)' structured data template support
* Correct title setting process

Version 0.4.2(2019-02-24)
-------------------------

* Update content parse process to consider three dashes(`---`) as delimiter string(#14)
    * If you want to check the content preview with [Visual Studio Code](https://code.visualstudio.com/), set file extension to '.md' and use three dashes(`---`) as a delimiter string instead of three dots(`...`)
* Add '[Review](https://developers.google.com/search/docs/data-types/review-snippet)' structured data template
    * Support types are `Book` and `Thing`

Version 0.4.1(2019-02-09)
-------------------------

* Replace `$_SERVER` super global variable reference with `filter_input` function(#13)
* Add web service support
    * Twitter timeline
    * Twitter tweet control
* Update documents a bit

Version 0.4.0(2019-01-02)
-------------------------

* Reconstruct template(#12)
* Add web service support
    * Google Analytics
    * Google custom search engine
    * Google AdSense(automatic ads only)
* Fix some bugs

Version 0.3.0(2018-12-23)
-------------------------

* Add structured data support(#5)
* Improve content image handling
* Correct handling of content including mustache('{{')
* Update a lot of codes
    * Follow php 7.2 update
    * Try to follow [PSR-1](https://www.php-fig.org/psr/psr-1/) and [PSR-2](https://www.php-fig.org/psr/psr-2/)
    * Refactoring

Version 0.2.1(2018-10-05)
-------------------------

* Add project logo(#3)
* Improve description handling(avoid duplication with the body text)
* Refactor some codes

Version 0.2.0(2018-09-22)
-------------------------

* Add tag-based related contents extraction process(#11)
    * Related contents are set to `related_contents` template variable
* Change template variables name `image_path` to `image_url` and set url insted of path

Version 0.1.5(2018-08-25)
-------------------------

* Add new template variables `theme_path` and `exclude_from_List`
* Correct encoded path parameter handling(#10)
* Modify escape processing when sending succeeding contents(#9)

Version 0.1.4(2018-06-16)
-------------------------

* Change markdown parser from [cebe/markdown](http://markdown.cebe.cc/) to [Parsedown](http://parsedown.org/)(#8)
* Fix some bugs and update some documents

Version 0.1.3(2018-06-03)
-------------------------

* Update OGP image source processing(#6)
* Add new template variables `latest_content_date_and_time` and `oldest_content_date_and_time`(#7)

Version 0.1.2(2018-04-23)
-------------------------

Improve path parameter check process(#4).

Version 0.1.1(2018-03-31)
-------------------------

Fix some bugs and update some documents.

Version 0.1.0(2018-03-18)
-------------------------

First preview release.