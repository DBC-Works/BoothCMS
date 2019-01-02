<?php
/*
 * BoothCMS configuration
 */

//
// Site information
//
// Site title
$config['site_title'] = 'BoothCMS';
// Site subtitle(mainly for Atom Syndication format)
$config['site_subtitle'] = null;
// Site url
$config['site_url'] = 'http://example.com';
// Site author
$config['site_author'] = 'D.B.C.';
// Site description
$config['site_description'] = 'BoothCMS is a simple flat file CMS.';
// Site author twitter account
$config['site_author_twitter'] = '@example';

//
// Environment
//

$config['in_test'] = false;

// Site language(ISO-639-1 language code or null(use system default))
$config['lang'] = null;
// Timezone(if null, use system default))
// http://php.net/manual/en/timezones.php
$config['timezone'] = 'UTC';

//
// Rendering setting
//

// Default site representation image path(to use [Open Graph protocol](http://ogp.me/)).
$config['site_image_path'] = '/views/themes/default/BoothCMS-logo-400x200.png';
// Contents count per page
$config['contents_per_page'] = 5;
// View theme
$config['theme'] = 'default';
// default template file name
$config['default_template'] = 'index.html';
// default support contents type(recent-update / ...)
$config['default_support_contents'] = 'recent-update';

// Excerpt limit length(in letters)
$config['excerpt_letter_limit_length'] = 300;
// Date and time format
// http://php.net/manual/en/class.datetime.php
$config['format_datetime'] = DateTime::ATOM;
// Max related contents count
$config['max_related_contents_count'] = 5;
// Structured data type array to render every contents
$config['common_contents_structured_data_types'] = [ ];

//
// Site metadata
//

// The 'tag' URI Scheme(RFC 4151) style id(for Atom syndication format)
// https://www.ietf.org/rfc/rfc4151.txt
$config['site_rfc4151_id'] = 'tag:example@example.com,2018-01-01:BoothCMS';
// Sitemap change frequency
// https://www.sitemaps.org/protocol.html#changefreqdef
$config['sitemap_changefreq'] = 'monthly';

//
// Service information
//

// Google Analytics tracking id
// $config['google_analytics_tracking_id'] = '';
// Google AdSense publisher id
// $config['google_adsense_publisher_id'] = '';
// Google custom search engine id
// $config['google_custom_search_engine_id'] = '';

//
// Twig settings
//
$config['twig_enable_cache'] = false;
$config['twig_enable_debug'] = false;
