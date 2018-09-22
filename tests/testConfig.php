<?php
require_once __DIR__ . '/../app/config.php';

/**
 * BoothCMS test configuration
 */

/*
 * Environment
 */
$config['in_test'] = true;
$config['lang'] = 'en';
$config['timezone'] = 'UTC';
$config['site_image_path'] = '/image.png';

//$config['fmt_datetime'] = DateTime::ATOM;
$config['format_datetime'] = 'Y/m/d H:m:s';
?>