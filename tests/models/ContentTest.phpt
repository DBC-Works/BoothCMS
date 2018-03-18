--TEST--
Content -- Test for Content class
--FILE--
<?php
require_once __DIR__ . '/../../app/models/Content.php';

echo "- 404\n";
$yamlContent = Content::load(__DIR__ . '/../../app/contents/404.yaml');
echo 'Can list up: ' . ($yamlContent->canListUp() ? 'yes' : 'no') . "\n";
echo 'Has template: ' . ($yamlContent->hasTemplate() ? 'yes' : 'no') . "\n";
echo 'Title(en): ' . $yamlContent->getTitle('en') . "\n";
echo 'Title(ja): ' . $yamlContent->getTitle('ja') . "\n";
echo 'Body(en): ' . $yamlContent->getRawBody('en') . "\n";
echo 'Body(ja): ' . $yamlContent->getRawBody('ja') . "\n";

echo "\n- index(yaml style header content)\n";
$yamlContent = Content::load(__DIR__ . '/../../app/contents/index.yaml');
echo 'Can list up: ' . ($yamlContent->canListUp() ? 'yes' : 'no') . "\n";
echo 'Has template: ' . ($yamlContent->hasTemplate() ? 'yes' : 'no') . "\n";
echo 'Title: ' . $yamlContent->getTitle() . "\n";
echo 'Has author: ' . ($yamlContent->hasAuthor() ? 'yes' : 'no') . "\n";
echo 'Date and time: ' . $yamlContent->getDateAndTime()->format('Y-m-d\TH:i:s') . "\n";
echo 'Category: ' . $yamlContent->getCategory() . "\n";
echo 'Has body: ' . (0 < mb_strlen($yamlContent->getRawBody()) ? 'yes' : 'no') . "\n";
$part = $yamlContent->getBeginningOfBody(100);
echo "Beginning of body: \n";
var_dump($part->part);
echo 'Beginning of body has following: ' . ($part->hasFollowing ? 'yes' : 'no') . "\n";

echo "\n- CHANGELOG(comment style header content)\n";
$mdContent = Content::load(__DIR__ . '/../../app/contents/CHANGELOG.yaml');
echo 'Can list up: ' . ($mdContent->canListUp() ? 'yes' : 'no') . "\n";
echo 'Has template: ' . ($mdContent->hasTemplate() ? 'yes' : 'no') . "\n";
echo 'Title: ' . $mdContent->getTitle() . "\n";
echo 'Author: ' . $mdContent->getAuthor() . "\n";
echo 'Date and time: ' . $mdContent->getDateAndTime()->format('Y-m-d\TH:i:s') . "\n";
echo 'Category: ' . $mdContent->getCategory() . "\n";
echo 'Has target text: ' . ($yamlContent->hasTargetText() ? 'yes' : 'no') . "\n";
echo 'Has body: ' . (0 < mb_strlen($mdContent->getRawBody()) ? 'yes' : 'no') . "\n";

echo "\n- feed\n";
$yamlContent = Content::load(__DIR__ . '/../../app/contents/feed.yaml');
echo 'Can list up: ' . ($yamlContent->canListUp() ? 'yes' : 'no') . "\n";
echo 'Has template: ' . ($yamlContent->hasTemplate() ? 'yes' : 'no') . "\n";
echo 'Template: ' . $yamlContent->getTemplate() . "\n";
echo 'Has target text: ' . ($yamlContent->hasTargetText() ? 'yes' : 'no') . "\n";
echo 'Target text: ' . $yamlContent->getTargetText() . "\n";
echo 'Has body: ' . (0 < mb_strlen($yamlContent->getRawBody()) ? 'yes' : 'no') . "\n";
?>
--EXPECT--
- 404
Can list up: no
Has template: no
Title(en): Not Found(404)
Title(ja): 見つかりませんでした(404)
Body(en): Not Found.(404)
Body(ja): 見つかりませんでした。(404)

- index(yaml style header content)
Can list up: yes
Has template: no
Title: BoothCMS: a simple Flat file CMS
Has author: no
Date and time: 2018-03-18T23:59:59
Category: Software
Has body: yes
Beginning of body: 
string(96) "BoothCMS is a simple Flat file CMS. Features No database No administration function - you create"
Beginning of body has following: yes

- CHANGELOG(comment style header content)
Can list up: yes
Has template: no
Title: CHANGELOG
Author: D.B.C.
Date and time: 2018-03-18T00:00:00
Category: Software
Has target text: no
Has body: yes

- feed
Can list up: no
Has template: yes
Template: atom.xml
Has target text: yes
Target text: description
Has body: no
