<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/ContentsProvider.php';

final class ContentsProviderTest extends TestCase
{
    //
    // Fixtures
    //

    private $provider;

    protected function setUp(): void {
        $this->provider = new ContentsProvider(__DIR__ . '/../../app/contents/', new DateTime());
    }

    //
    // Test cases
    //

    public function testListUpContents(): void {
        $now = new DateTime();
        $listUpContents = $this->provider->getListUpContents();
        self::assertFalse(array_key_exists('/404', $listUpContents), '404 is not in list');
        self::assertFalse(array_key_exists('/feed', $listUpContents), 'feed is not in list');
        foreach ($listUpContents as $key => $value) {
            self::assertTrue($value->canListUp());
            self::assertTrue($value->released($now));
        }
        self::assertEquals($this->provider->getLatestContentDateAndTime(), current($listUpContents)->getDateAndTime());
        self::assertEquals($this->provider->getOldestContentDateAndTime(), end($listUpContents)->getDateAndTime());

        $currentContentsCount = count($listUpContents);

        $past = new DateTime('2018-03-17');
        $provider = new ContentsProvider(__DIR__ . '/../../app/contents/', $past);
        $listUpContents = $provider->getListUpContents();
        self::assertLessThan($currentContentsCount, count($listUpContents));
        foreach ($listUpContents as $key => $value) {
            self::assertTrue($value->canListUp());
            self::assertTrue($value->released($past));
        }

        $future = new DateTime('2018-03-18');
        $provider = new ContentsProvider(__DIR__ . '/../../app/contents/', $future);
        $listUpContents = $provider->getListUpContents();
        self::assertEquals($currentContentsCount, count($listUpContents));
        foreach ($listUpContents as $key => $value) {
            self::assertTrue($value->canListUp());
            self::assertTrue($value->released($future));
        }
    }

    public function testGetContent(): void {
        // index
        $content = $this->provider->getContent('/index');
        self::assertEquals('BoothCMS: a simple flat file CMS', $content->target->content->getTitle());
        self::assertEquals('/index', $content->target->path);
        self::assertNull($content->next);
        self::assertNotNull($content->prev);

        // Feed
        self::assertTrue($this->provider->hasContent('/feed'), 'Has feed');
        $feed = $this->provider->getContent('/feed');
        self::assertTrue(is_null($feed) === false && is_null($feed->target) === false, 'Valid feed');
        self::assertTrue(is_null($feed->prev) && is_null($feed->next), 'Standalone');
    }

    public function testGetRecentPublishContents(): void {
        $recentPublishContents = $this->provider->getRecentPublishContents(5, 0);
        self::assertLessThanOrEqual(5, count($recentPublishContents->part));

        $latestContent = current($this->provider->getListUpContents());
        self::assertEquals($latestContent->getTitle(), current($recentPublishContents->part)->getTitle());
    }

    public function testGetRecentUpdateContents(): void {
        $recentUpdateContents = $this->provider->getRecentUpdateContents(5, 0);
        self::assertLessThanOrEqual(5, count($recentUpdateContents->part));
    }

    public function testGetTagSet(): void {
        $tag_set = $this->provider->getTagSet();
        self::assertGreaterThan(0, count($tag_set));
        foreach ($tag_set as $key => $value) {
            self::assertEquals(count($value), count($this->provider->getTaggedContents($key, 100, 0)->part));
        }
    }

    public function testGetRelatedContents(): void {
        $listUpContents = $this->provider->getListUpContents();
        $content = end($listUpContents);
        $tags = $content->getTags();
        $relatedContents = $this->provider->getRelatedContentsOf($content, 100);
        foreach ($relatedContents as $relatedContent) {
            $relatedTags = array_intersect($tags, $relatedContent->content->getTags());
            self::assertGreaterThan(0, count($relatedTags));
        }
    }

    public function testGetLanguages(): void {
        $expect = [
        ];
        $actual = $this->provider->getLanguages();
        self::assertTrue(empty(array_diff($expect, $actual)));
    }
}