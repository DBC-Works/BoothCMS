--TEST--
Rendering Google custom search engine script test
--FILE--
<?php
require_once __DIR__ . '/../testEnv.php';
require_once __DIR__ . '/../testConfig.php';
require_once __DIR__ . '/TestUtilities.php';
require_once __DIR__ . '/../../app/models/ContentsProvider.php';
require_once __DIR__ . '/../../app/controllers/Controller.php';


function reportScriptElementCount(string $html_string) {
    // echo $html_string . "\n";
    $html = createSimpleXmlElement($html_string);
    echo '  Script count: ' . countElements($html, '//default:script[@class="google-cse"]') . "\n";
}

$provider = new ContentsProvider(__DIR__ . '/../../app/contents');

echo "Without Google custom search engine:\n";
$controller = new Controller($provider, $config, $env);
reportScriptElementCount($controller->render('/index'));

echo "With Google custom search engine:\n";
$env['as_develop'] = false;
$config['google_custom_search_engine_id'] = 'cse id';
$controller = new Controller($provider, $config, $env);
reportScriptElementCount($controller->render('/index'));
?>
--EXPECT--
Without Google custom search engine:
  Script count: 0
With Google custom search engine:
  Script count: 1
