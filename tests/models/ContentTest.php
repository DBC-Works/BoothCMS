<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/Content.php';

final class ContentTest extends TestCase
{
    //
    // Utilities
    //

    private static function loadContent(string $content): Content {
        return Content::load(__DIR__ . '/../../app/contents/' . $content);
    }

    //
    // Test cases
    //

    public function testLoad(): void {
        // 404
        $yamlContent = self::loadContent('404.yaml');
        self::assertFalse($yamlContent->canListUp());
        self::assertFalse($yamlContent->hasTemplate());
    
        // index(yaml style header content)
        $yamlContent = self::loadContent('index.yaml');
        self::assertTrue($yamlContent->canListUp());
        self::assertFalse($yamlContent->hasTemplate());
        self::assertEquals('BoothCMS: a simple flat file CMS', $yamlContent->getTitle());
        self::assertFalse($yamlContent->hasAuthor());
        self::assertEquals('2999-12-31T23:59:59', $yamlContent->getDateAndTime()->format('Y-m-d\TH:i:s'));
        self::assertEquals('Software', $yamlContent->getCategory());
        self::assertGreaterThan(0, mb_strlen($yamlContent->getRawBody()));

        // CHANGELOG(comment style header content) 
        $mdContent = self::loadContent('CHANGELOG.yaml');
        self::assertTrue($mdContent->canListUp());
        self::assertFalse($mdContent->hasTemplate());
        self::assertEquals('CHANGELOG', $mdContent->getTitle());
        self::assertEquals('D.B.C.', $mdContent->getAuthor());
        self::assertEquals('2018-03-31T00:00:00', $mdContent->getDateAndTime()->format('Y-m-d\TH:i:s'));
        self::assertEquals('Software', $mdContent->getCategory());
        self::assertFalse($mdContent->hasTargetText());
        self::assertGreaterThan(0, mb_strlen($mdContent->getRawBody()));

        // feed 
        $yamlContent = self::loadContent('feed.yaml');
        self::assertFalse($yamlContent->canListUp());
        self::assertTrue($yamlContent->hasTemplate());
        self::assertEquals('atom.xml', $yamlContent->getTemplate());
        self::assertTrue($yamlContent->hasTargetText());
        self::assertEquals('beginning', $yamlContent->getTargetText());
        self::assertEquals(0, mb_strlen($yamlContent->getRawBody()));
    }

    public function testGetLanguages(): void {
        $expect = [
            'ja',
            'en'
        ];
        $yamlContent = self::loadContent('404.yaml');
        $actual = $yamlContent->getLanguages();
        self::assertTrue(empty(array_diff($expect, $actual)));
    }

    public function testGetTitle(): void {
        $titles = [
            'ja' => '見つかりませんでした(404)',
            'en' => 'Not Found(404)'
        ];

        $yamlContent = self::loadContent('404.yaml');
        foreach ($titles as $lang => $title) {
            self::assertEquals($title, $yamlContent->getTitle($lang));
        }
        self::assertEquals($titles['en'], $yamlContent->getTitle());
    }

    public function testGetRawBody(): void {
        $bodies = [
            'ja' => '見つかりませんでした。(404)',
            'en' => 'Not Found.(404)'
        ];

        $yamlContent = self::loadContent('404.yaml');
        foreach ($bodies as $lang => $body) {
            self::assertEquals($body, $yamlContent->getRawBody($lang));
        }
        self::assertEquals($bodies['en'], $yamlContent->getRawBody());
    }

    public function testGetBeginningOfBody(): void {
        $yamlContent = self::loadContent('index.yaml');
        $part = $yamlContent->getBeginningOfBody(100);
        self::assertEquals('BoothCMS is a simple flat file CMS. Features No database No administration function - you create', $part->part);
        self::assertTrue($part->hasFollowing);
    }

    public function testReleased(): void {
        $noRestrictionContent = self::loadContent('index.yaml');
        $restrictedContent = self::loadContent('blog/2018/3/19.yaml');

        $now = new DateTime();
        self::assertTrue($noRestrictionContent->released($now));
        self::assertTrue($restrictedContent->released($now));

        $past = new DateTime("2018-03-17");
        self::assertTrue($noRestrictionContent->released($past));
        self::assertFalse($restrictedContent->released($past));

        $future = new DateTime("2018-03-18");
        self::assertTrue($noRestrictionContent->released($future));
        self::assertTrue($restrictedContent->released($future));
   }
}