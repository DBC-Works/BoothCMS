--TEST--
Sitemap Format rendering test
--FILE--
<?php
require_once __DIR__ . '/../../app/models/ContentsProvider.php';
require_once __DIR__ . '/../../app/controllers/Controller.php';
require_once __DIR__ . '/../testEnv.php';
require_once __DIR__ . '/../testConfig.php';

$provider = new ContentsProvider(__DIR__ . '/../../app/contents');
$controller = new Controller($provider, $config, $env);
$sitemap_string = $controller->render('/sitemap');
//echo $sitemap_string . "\n";
$sitemap = new SimpleXMLElement($sitemap_string);
echo 'Entry count: ' . count($sitemap->url) . "\n";
?>
--EXPECT--
Entry count: 19
