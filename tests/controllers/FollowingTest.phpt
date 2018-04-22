--TEST--
Following rendering test
--FILE--
<?php
require_once __DIR__ . '/../../app/models/ContentsProvider.php';
require_once __DIR__ . '/../../app/controllers/Controller.php';
require_once __DIR__ . '/../testEnv.php';
require_once __DIR__ . '/../testConfig.php';

$config['contents_per_page'] = 2;

$provider = new ContentsProvider(__DIR__ . '/../../app/contents');
$controller = new Controller($provider, $config, $env);
$json_string = $controller->render('/following/1/doc/');
//echo $json_string . "\n";
$json = json_decode($json_string);
//echo json_encode($json) . "\n";
echo "- Valid parameter\n";
echo 'Has following: ' . ($json->hasFollowing ? 'yes' : 'no') . "\n";
echo 'Following count: ' . count($json->contents) . "\n";

echo "\n- No parameter\n";
$html_string = $controller->render('/following');
//echo $html_string . "\n";
$html = new SimpleXMLElement($html_string);
echo 'title: ' . $html->head->title . "\n";

?>
--EXPECT--
- Valid parameter
Has following: no
Following count: 2

- No parameter
title: Not Found(404) - BoothCMS
