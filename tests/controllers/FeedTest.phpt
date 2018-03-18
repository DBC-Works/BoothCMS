--TEST--
Atom Syndication Format rendering test
--FILE--
<?php
require_once __DIR__ . '/../../app/models/ContentsProvider.php';
require_once __DIR__ . '/../../app/controllers/Controller.php';
require_once __DIR__ . '/../testEnv.php';
require_once __DIR__ . '/../testConfig.php';

$provider = new ContentsProvider(__DIR__ . '/../../app/contents');
$controller = new Controller($provider, $config, $env);
$atom_string = $controller->render('/feed');
//echo $atom_string . "\n";
$atom = new SimpleXMLElement($atom_string);
echo 'Entry count: ' . count($atom->entry) . "\n";
?>
--EXPECT--
Entry count: 5
