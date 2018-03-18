--TEST--
Index rendering test
--FILE--
<?php
require_once __DIR__ . '/../testEnv.php';
require_once __DIR__ . '/../testConfig.php';
require_once __DIR__ . '/../../app/models/ContentsProvider.php';
require_once __DIR__ . '/../../app/controllers/Controller.php';

$provider = new ContentsProvider(__DIR__ . '/../../app/contents');
$controller = new Controller($provider, $config, $env);
$html_string = $controller->render('/index');
//echo $html_string . "\n";

$html = new SimpleXMLElement($html_string);

echo 'title: ' . $html->head->title . "\n";
?>
--EXPECT--
title: BoothCMS: a simple Flat file CMS - BoothCMS
