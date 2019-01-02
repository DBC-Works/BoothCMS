--TEST--
Rendering content that contains structured data test
--FILE--
<?php
require_once __DIR__ . '/../testEnv.php';
require_once __DIR__ . '/../testConfig.php';
require_once __DIR__ . '/TestUtilities.php';
require_once __DIR__ . '/../../app/models/ContentsProvider.php';
require_once __DIR__ . '/../../app/controllers/Controller.php';

function reportScriptElementInBody(string $html_string) {
    // echo $html_string . "\n";
    $html = createSimpleXmlElement($html_string);
    foreach ($html->xpath('//default:body//default:script[@type="application/ld+json"]') as $script) {
        $json = json_decode((string)$script);
        echo '  Structured data type: ' . $json->{'@type'} . "\n";
        if ($json->image) {
            echo '  Image: ' . $json->image->url . "\n";
        }
    }
}

$provider = new ContentsProvider(__DIR__ . '/../../app/contents');

echo "Content with structured data specified:\n";
$controller = new Controller($provider, $config, $env);
reportScriptElementInBody($controller->render('/test-data/stuctured-data'));

echo "Content with structured data specified, render with configuration:\n";
$config['common_contents_structured_data_types'] = [ 'BlogPosting' ];
$controller = new Controller($provider, $config, $env);
reportScriptElementInBody($controller->render('/test-data/stuctured-data'));

echo "Content without structured data, render with configuration:\n";
$controller = new Controller($provider, $config, $env);
reportScriptElementInBody($controller->render('/test-data/representation-image/specification-in-header'));
?>
--EXPECT--
Content with structured data specified:
  Structured data type: BlogPosting
  Image: http://example.com/image.png
Content with structured data specified, render with configuration:
  Structured data type: BlogPosting
  Image: http://example.com/image.png
Content without structured data, render with configuration:
  Structured data type: BlogPosting
  Image: https://i.ytimg.com/vi/uacjJ4_dwrE/sddefault.jpg
