--TEST--
Rendering Google AdSense automatic ads script test
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
    echo '  Script count: ' . countElements($html, '//default:head/default:script[@class="google-adsense-automatic-ads"]') . "\n";
}

$provider = new ContentsProvider(__DIR__ . '/../../app/contents');

echo "Without Google AdSense automatic ads:\n";
$controller = new Controller($provider, $config, $env);
reportScriptElementCount($controller->render('/index'));

echo "With Google AdSense automatic ads:\n";
$env['as_develop'] = false;
$config['google_adsense_publisher_id'] = 'publisher id';
$controller = new Controller($provider, $config, $env);
reportScriptElementCount($controller->render('/index'));
?>
--EXPECT--
Without Google AdSense automatic ads:
  Script count: 0
With Google AdSense automatic ads:
  Script count: 2
