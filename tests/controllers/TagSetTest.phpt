--TEST--
Index rendering test
--FILE--
<?php
require_once __DIR__ . '/../testEnv.php';
require_once __DIR__ . '/../testConfig.php';
require_once __DIR__ . '/../../app/models/ContentsProvider.php';
require_once __DIR__ . '/../../app/controllers/Controller.php';

$provider = new ContentsProvider(__DIR__ . '/../../app/contents');
$controller = new Controller($provider, $config, $env);

echo "- Tag set\n";
$html_string = $controller->render('/tags');
//echo $html_string . "\n";

$html = new SimpleXMLElement($html_string);
echo 'title: ' . $html->head->title . "\n";

echo "\n- Tag(LOG)\n";
$html_string = $controller->render('/tags/Log');
//echo $html_string;
$html = new SimpleXMLElement($html_string);
echo 'title: ' . $html->head->title . "\n";

foreach ($html->xpath('//a') as $anchor) {
    foreach($anchor->attributes() as $name => $value) {
        if ($name === 'href') {
            if ((mb_strpos($value, 'http') === 0) === false) {
                throw new Exception('Invalid href: ' . $value);
            }
        }
    }
}
foreach ($html->xpath('//link') as $link) {
    foreach($link->attributes() as $name => $value) {
        if ($name === 'href') {
            if ((mb_strpos($value, 'http') === 0) === false) {
                throw new Exception('Invalid href: ' . $value);
            }
        }
    }
}

?>
--EXPECT--
- Tag set
title: Tag - BoothCMS

- Tag(LOG)
title: Tag: Log - BoothCMS
