BoothCMS
========

![BoothCMS logo](./app/views/themes/default/BoothCMS-logo-400x200.png)

BoothCMS is a simple flat file CMS.

Features
--------

* No database
* No administration function - create contents as files and copy to the web server
* Content format is a composite of [YAML](http://yaml.org/)(headers) and [Markdown](https://learn.getgrav.org/content/markdown)(body)
    * This format is known as YAML front matter, but in BoothCMS you can also use three dots(`...`) as delimiter string betweeh header and body.
* Template can be specified for each content
* List display control function
* Built-in feed([Atom Syndication Format](https://tools.ietf.org/html/rfc4287)) and [Sitemaps](https://www.sitemaps.org/) generator
* Built-in tag support
* Server-side and client-side hybrid rendering
* Tag-based related contents extraction
* Structured data support
    * Default support types are 'BlogPosting', 'Review'(`Book` and `Thing`) and 'Video'
* Built-in web service support
    * Google Analytics
    * Google custom search engine
    * Google AdSense(automatic ads only)
    * Twitter timeline
    * Twitter tweet control

Requirements
------------

* Web server that can rewrite url
    * Apache HTTP Server
    * Microsoft Internet Information Service
    * Microsoft Azure App Service Web Apps
    * etc
* PHP 7.2 or later