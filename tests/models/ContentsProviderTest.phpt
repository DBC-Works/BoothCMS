--TEST--
ContentProvider -- Test for ContentsProivider class
--FILE--
<?php
require_once __DIR__ . '/../../app/models/ContentsProvider.php';

$provider = new ContentsProvider(__DIR__ . '/../../app/contents/');

$listUpContents = $provider->getListUpContents();
echo '404 is not in list: ' . (array_key_exists('/404', $listUpContents) !== false ? 'no' : 'yes') . "\n";
$listUpContentsCount = count($listUpContents);
echo 'Content count: ' . count($listUpContents) . "\n";
$latestContent = current($listUpContents);
echo "Latest content title: " . $latestContent->getTitle();
echo "\nValid latest content date and time: " . ($latestContent->getDateAndTime() == $provider->getLatestContentDateAndTime() ? 'yes' : 'no');
echo "\nValid oldest content date and time: " . (end($listUpContents)->getDateAndTime() == $provider->getOldestContentDateAndTime() ? 'yes' : 'no');

echo "\n\nHas feed: " . ($provider->hasContent('/feed') ? 'yes' : 'no'). "\n";
echo 'feed is not in list: ' . (array_key_exists('/feed', $listUpContents) !== false ? 'no' : 'yes') . "\n";
$info = $provider->getContent('/feed');
echo 'Valid feed: ' . (is_null($info) === false && is_null($info->target) === false ? 'yes' : 'no') . "\n";
echo 'Feed path: ' . $info->target->path. "\n";
echo 'Standalone: ' . (is_null($info->prev) && is_null($info->next) ? 'yes' : 'no') . "\n\n";

$recentPublishContents = $provider->getRecentPublishContents(5, 0);
echo 'Valid recent publish content count: ' . count($recentPublishContents->part) . "\n";
echo 'Valid latest publish content title: ' . ($latestContent->getTitle() ===  current($recentPublishContents->part)->getTitle() ? 'yes' : 'no') . "\n";

$recentUpdateContents = $provider->getRecentUpdateContents(5, 0);
echo 'Valid recent update content count: ' . count($recentUpdateContents->part) . "\n";

echo "\n- Tag set\n";
$tag_set = $provider->getTagSet();
echo 'Tag count: ' . count($provider->getTagSet()) . "\n";
foreach ($tag_set as $key => $value) {
    echo $key . ': ' . count($value) . '(' . count($provider->getTaggedContents($key, 100, 0)->part) . ')' . "\n";
}

echo "\n- index(yaml style header content)\n";
$content = $provider->getContent('/index');
echo 'Title: ' . $content->target->content->getTitle() . "\n";
echo 'Path: ' . $content->target->path . "\n";
echo 'Has next: ' . (is_null($content->next) ? 'no' : 'yes') . "\n";
echo 'Has prev: ' . (is_null($content->prev) ? 'no' : 'yes') . "\n";

echo "\n- related contents\n";
$list_up_contents = $provider->getListUpContents();
$content = end($list_up_contents);
echo 'Base tags: ' . implode(' ', $content->getTags()) . "\n";
$related_contents = $provider->getRelatedContentsOf($content, 100);
foreach ($related_contents as $related_content) {
    echo 'Related tags: ' . implode(' ', $related_content->content->getTags()) . "\n";
}
?>
--EXPECT--
404 is not in list: yes
Content count: 24
Latest content title: BoothCMS: a simple flat file CMS
Valid latest content date and time: yes
Valid oldest content date and time: yes

Has feed: yes
feed is not in list: yes
Valid feed: yes
Feed path: /feed
Standalone: yes

Valid recent publish content count: 5
Valid latest publish content title: yes
Valid recent update content count: 5

- Tag set
Tag count: 10
Release: 12(12)
Announcement: 12(12)
Blog: 12(12)
Test content: 5(5)
Instruction: 4(4)
Install: 3(3)
Summary: 1(1)
Log: 1(1)
Specification: 1(1)
License: 1(1)

- index(yaml style header content)
Title: BoothCMS: a simple flat file CMS
Path: /index
Has next: no
Has prev: yes

- related contents
Base tags: Release Announcement Blog
Related tags: Release Announcement Blog
Related tags: Release Announcement Blog
Related tags: Release Announcement Blog
Related tags: Release Announcement Blog
Related tags: Release Announcement Blog
Related tags: Release Announcement Blog
Related tags: Release Announcement Blog
Related tags: Release Announcement Blog
Related tags: Release Announcement Blog
Related tags: Release Announcement Blog
Related tags: Release Announcement Blog
