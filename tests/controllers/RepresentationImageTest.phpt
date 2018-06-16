--TEST--
Tag rendering test
--FILE--
<?php
require_once __DIR__ . '/../testEnv.php';
require_once __DIR__ . '/../testConfig.php';
require_once __DIR__ . '/../../app/models/ContentsProvider.php';
require_once __DIR__ . '/../../app/controllers/Controller.php';

function getOgpImageUrl($html_string) {
    $images = array();
    mb_ereg_search_init($html_string, "<meta property=\"og:image\" content=\"(.+?)\"");
    while (mb_ereg_search()) {
        $matches = mb_ereg_search_getregs();
        if ($matches[0] !== '') {
            $images[] = $matches[1];
        }
    }
    return 0 < count($images) ? $images[0] : '';
}

$provider = new ContentsProvider(__DIR__ . '/../../app/contents');
$controller = new Controller($provider, $config, $env);

echo "- In header\n";
$html_string = $controller->render('/test-data/representation-image/specification-in-header');
$html = new SimpleXMLElement($html_string);
echo getOgpImageUrl($html_string) . "\n";

echo "\n- In body(absolute URL)\n";
$html_string = $controller->render('/test-data/representation-image/specification-in-body-absolute-url');
//echo $html_string . "\n";
$html = new SimpleXMLElement($html_string);
echo getOgpImageUrl($html_string) . "\n";

echo "\n- In body(path)\n";
$html_string = $controller->render('/test-data/representation-image/specification-in-body-path');
$html = new SimpleXMLElement($html_string);
echo getOgpImageUrl($html_string) . "\n";

echo "\n- In body(absolute url, multiple)\n";
$html_string = $controller->render('/test-data/representation-image/multiple-specification-in-body');
//echo $html_string . "\n";
$html = new SimpleXMLElement($html_string);
echo getOgpImageUrl($html_string) . "\n";

--EXPECT--
- In header
https://i.ytimg.com/vi/uacjJ4_dwrE/sddefault.jpg

- In body(absolute URL)
https://i.ytimg.com/vi/uacjJ4_dwrE/sddefault.jpg

- In body(path)
http://example.com/vi/uacjJ4_dwrE/sddefault.jpg

- In body(absolute url, multiple)
https://i.ytimg.com/vi/R6T4CFtS22k/sddefault.jpg