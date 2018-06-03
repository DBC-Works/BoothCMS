--TEST--
Template variables setting test
--FILE--
<?php
require_once __DIR__ . '/../../app/models/ContentsProvider.php';
require_once __DIR__ . '/../../app/controllers/Controller.php';
require_once __DIR__ . '/../testEnv.php';
require_once __DIR__ . '/../testConfig.php';
$env['root_path'] = realpath(__DIR__ . '/../');

$provider = new ContentsProvider(__DIR__ . '/../../tests/contents');
$controller = new Controller($provider, $config, $env);
$html_string = $controller->render('/template-variables');
//echo $html_string . "\n";
$html = new SimpleXMLElement($html_string);
foreach ($html->body->ul->xpath('//li') as $li) {
    echo $li . "\n";
}
?>
--EXPECT--
Latest content date and time: 2018-06-01
Oldest content date and time: 2018-06-01
