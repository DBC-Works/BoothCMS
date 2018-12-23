--TEST--
Rendering content that contains structured data test
--FILE--
<?php
require_once __DIR__ . '/../testEnv.php';
require_once __DIR__ . '/../testConfig.php';
require_once __DIR__ . '/../../app/models/ContentsProvider.php';
require_once __DIR__ . '/../../app/controllers/Controller.php';

$provider = new ContentsProvider(__DIR__ . '/../../app/contents');

echo "Content with structured data specified:\n";
$controller = new Controller($provider, $config, $env);
$html_string = $controller->render('/test-data/stuctured-data');
// echo $html_string . "\n";
$html = new SimpleXMLElement($html_string);
foreach ($html->body->script as $script) {
    $attributes = $script->attributes();
    if ($attributes['type'] == 'application/ld+json') {
        $json = json_decode((string)$script);
        echo 'Structured data type: ' . $json->{'@type'} . "\n";
    }
}

echo "\nContent without structured data:\n";
$config['common_contents_structured_data_types'] = [ 'BlogPosting' ];
$controller = new Controller($provider, $config, $env);
$html_string = $controller->render('/test-data/representation-image/specification-in-header');
// echo $html_string . "\n";
$html = new SimpleXMLElement($html_string);
foreach ($html->body->script as $script) {
    $attributes = $script->attributes();
    if ($attributes['type'] == 'application/ld+json') {
        $json = json_decode((string)$script);
        echo 'Structured data type: ' . $json->{'@type'} . "\n";
        echo 'Image: ' . $json->image->url . "\n";
    }
}
?>
--EXPECT--
Content with structured data specified:
Structured data type: BlogPosting

Content without structured data:
Structured data type: BlogPosting
Image: https://i.ytimg.com/vi/uacjJ4_dwrE/sddefault.jpg