--TEST--
Escape double mustaches test
--FILE--
<?php
require_once __DIR__ . '/../../app/models/ContentsProvider.php';
require_once __DIR__ . '/../../app/controllers/Controller.php';
require_once __DIR__ . '/../testEnv.php';
require_once __DIR__ . '/../testConfig.php';
$env['root_path'] = realpath(__DIR__ . '/../');

$provider = new ContentsProvider(__DIR__ . '/../../tests/contents');
$controller = new Controller($provider, $config, $env);
$html_string = $controller->render('/escape-double-mustaches');
// echo $html_string . "\n";
$html = new SimpleXMLElement($html_string);
foreach ($html->body->p as $p) {
    echo $p . "\n";
}
?>
--EXPECT--
BoothCMS escapes double mustaches('\u007b{').
BoothCMS also escapes triple mustaches('\u007b\u007b{').
BoothCMS also escapes quadruple mustaches('\u007b\u007b\u007b{').
