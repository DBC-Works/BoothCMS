--TEST--
Tag rendering test
--FILE--
<?php
require_once __DIR__ . '/../testEnv.php';
require_once __DIR__ . '/../testConfig.php';
require_once __DIR__ . '/../../app/models/ContentsProvider.php';
require_once __DIR__ . '/../../app/controllers/Controller.php';

function getOgpImageUrl($head) {
    $og_image = null;
    foreach ($head->meta as $meta) {
        foreach($meta->attributes() as $name => $value) {
            if ($name === 'property' && $value == 'og:image') {
                $og_image = $meta;
                break;
            }
        }
        if (is_null($og_image) === false) {
            break;
        }
    }
    foreach($og_image->attributes() as $name => $value) {
        if ($name === 'content') {
            return $value;
            break;
        }
    }
    return '';
}

$provider = new ContentsProvider(__DIR__ . '/../../app/contents');
$controller = new Controller($provider, $config, $env);

echo "- In header\n";
$html = new SimpleXMLElement($controller->render('/test-data/representation-image/specification-in-header'));
echo getOgpImageUrl($html->head) . "\n";

echo "\n- In body(absolute URL)\n";
$html = new SimpleXMLElement($controller->render('/test-data/representation-image/specification-in-body-absolute-url'));
echo getOgpImageUrl($html->head) . "\n";

echo "\n- In body(path)\n";
$html = new SimpleXMLElement($controller->render('/test-data/representation-image/specification-in-body-path'));
echo getOgpImageUrl($html->head) . "\n";

--EXPECT--
- In header
https://i.ytimg.com/vi/uacjJ4_dwrE/sddefault.jpg

- In body(absolute URL)
https://i.ytimg.com/vi/uacjJ4_dwrE/sddefault.jpg

- In body(path)
http://example.com/vi/uacjJ4_dwrE/sddefault.jpg