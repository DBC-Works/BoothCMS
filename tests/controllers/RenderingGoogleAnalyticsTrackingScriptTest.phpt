--TEST--
Rendering Google Analytics tracking script test
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
    echo '  Script count: ' . countElements($html, '//default:body/default:script[@class="google-analytics"]') . "\n";
}

$provider = new ContentsProvider(__DIR__ . '/../../app/contents');

echo "Without Google Analytics tracking:\n";
$controller = new Controller($provider, $config, $env);
reportScriptElementCount($controller->render('/index'));

echo "With Google Analytics tracking:\n";
$env['as_develop'] = false;
$config['google_analytics_tracking_id'] = 'tracking code';
$controller = new Controller($provider, $config, $env);
reportScriptElementCount($controller->render('/index'));
?>
--EXPECT--
Without Google Analytics tracking:
  Script count: 0
With Google Analytics tracking:
  Script count: 2
