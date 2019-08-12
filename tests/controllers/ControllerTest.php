<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/ContentsProvider.php';
require_once __DIR__ . '/../testEnv.php';
require_once __DIR__ . '/../testConfig.php';

require_once __DIR__ . '/../../app/controllers/Controller.php';

final class ControllerTest extends TestCase
{
    //
    // Utilities
    //

    private static $CONTENTS_PER_PAGE = 2;

    private static function createContentControllerWith(array $config, array $env): Controller {
        $env['root_path'] = realpath(__DIR__ . '/../../app');
        $config['contents_per_page'] = self::$CONTENTS_PER_PAGE;

        $provider = new ContentsProvider(__DIR__ . '/../../app/contents', new DateTime());
        return new Controller($provider, $config, $env);
    }

    private static function createContentControllerWithConfig(array $config): Controller {
        global $env;

        return self::createContentControllerWith($config, $env);
    }

    private static function createContentController(): Controller {
        global $config;

        return self::createContentControllerWithConfig($config);
    }

    private static function createTestContentController(): Controller {
        global $config, $env;

        $env['root_path'] = realpath(__DIR__ . '/../');
        $config['contents_per_page'] = self::$CONTENTS_PER_PAGE;

        $provider = new ContentsProvider(__DIR__ . '/../contents', new DateTime());
        return new Controller($provider, $config, $env);
    }

    private static function assertHref(array $elements): void {
        foreach ($elements as $element) {
            foreach($element->attributes() as $name => $value) {
                if ($name === 'href') {
                    self::assertEquals(0, mb_strpos($value, 'http'));
                }
            }
        }
    }

    private static function getOgpImageUrl($htmlString): string {
        $images = [];
        mb_ereg_search_init($htmlString, "<meta property=\"og:image\" content=\"(.+?)\"");
        while (mb_ereg_search()) {
            $matches = mb_ereg_search_getregs();
            if ($matches[0] !== '') {
                $images[] = $matches[1];
            }
        }
        return 0 < count($images) ? $images[0] : '';
    }

    private static function createSimpleXmlElement(string $htmlString): SimpleXMLElement {
        $html = new SimpleXMLElement($htmlString);
        foreach ($html->getDocNamespaces() as $prefix => $namespace) {
            if ($prefix === '') {
                $prefix = "default";
            }
            $html->registerXPathNamespace($prefix, $namespace);
        }
        return $html;
    }

    //
    // Fixtures
    //

    private $originalConfig;
    private $originalEnv;

    protected function setUp(): void {
        global $config, $env;

        $this->originalConfig = $config;
        $this->originalEnv = $env;
    }

    protected function tearDown(): void {
        global $config, $env;

        $config = $this->originalConfig;
        $env = $this->originalEnv;
    }

    //
    // Test cases
    //

    public function testRenderContent(): void {
        $controller = self::createContentController();

        // index
        $html = self::createSimpleXmlElement($controller->render('/index'));
        self::assertEquals('BoothCMS: a simple flat file CMS - BoothCMS', $html->head->title);
        self::assertHref($html->xpath('//a'));
        self::assertHref($html->xpath('//link'));

        // Feed
        $atom = self::createSimpleXmlElement($controller->render('/feed'));
        self::assertEquals(2, count($atom->entry));

        // Site map
        $sitemap = self::createSimpleXmlElement($controller->render('/sitemap'));
        $provider = new ContentsProvider(__DIR__ . '/../../app/contents', new DateTime());
        self::assertEquals(count($provider->getListUpContents()), count($sitemap->url));

        // Tag sets
        $html = self::createSimpleXmlElement($controller->render('/tags'));
        self::assertEquals('Tag - BoothCMS', $html->head->title);
        $html = self::createSimpleXmlElement($controller->render('/tags/Log'));
        self::assertEquals('Tag: Log - BoothCMS', $html->head->title);
        self::assertHref($html->xpath('//a'));
        self::assertHref($html->xpath('//link'));
        $html = self::createSimpleXmlElement($controller->render('/404'));
        self::assertEquals('Not Found(404) - BoothCMS', $html->head->title);
    }

    public function testRenderTestContent(): void {
        $controller = self::createTestContentController();

        // double mustaches
        $html = self::createSimpleXmlElement($controller->render('/escape-double-mustaches'));
        $expects = [
            "BoothCMS escapes double mustaches('{&#8203;{').",
            "BoothCMS also escapes triple mustaches('{&#8203;{&#8203;{').",
            "BoothCMS also escapes quadruple mustaches('{&#8203;{&#8203;{&#8203;{')."
        ];
        $index = 0;
        foreach ($html->body->p as $p) {
            self::assertEquals($expects[$index], mb_convert_encoding($p, 'HTML-ENTITIES'));
            ++$index;
        }

        // template variables
        $html = self::createSimpleXmlElement($controller->render('/template-variables'));
        $expects = [
            'Latest content date and time: 2018-12-08',
            'Oldest content date and time: 2018-06-01'
        ];
        $index = 0;
        foreach ($html->body->ul->xpath('//li') as $li) {
            self::assertEquals($expects[$index], $li);
            ++$index;
        }
    }

    public function testRenderFollowing(): void {
        $controller = self::createContentController();
        $json = json_decode($controller->render('/following/1/doc/'));

        // Valid parameter
        self::assertFalse($json->hasFollowing, 'Has followiing');
        self::assertEquals(self::$CONTENTS_PER_PAGE, count($json->contents), 'Followiing count');

        // No parameter
        $html = self::createSimpleXMLElement($controller->render('/following'));
        self::assertEquals('Not Found(404) - BoothCMS', $html->head->title);
    }


    public function testRenderRepresentationImage(): void {
        $controller = self::createContentController();

        // In header
        $htmlString = $controller->render('/test-data/representation-image/specification-in-header');
        self::assertEquals('https://i.ytimg.com/vi/uacjJ4_dwrE/sddefault.jpg', self::getOgpImageUrl($htmlString));

        // In body(absolute URL)
        $htmlString = $controller->render('/test-data/representation-image/specification-in-body-absolute-url');
        self::assertEquals('https://i.ytimg.com/vi/uacjJ4_dwrE/sddefault.jpg', self::getOgpImageUrl($htmlString));

        // In body(path) 
        $htmlString = $controller->render('/test-data/representation-image/specification-in-body-path');
        self::assertEquals('http://example.com/vi/uacjJ4_dwrE/sddefault.jpg', self::getOgpImageUrl($htmlString));

        // In body(absolute url, multiple)
        $htmlString = $controller->render('/test-data/representation-image/multiple-specification-in-body');
        self::assertEquals('https://i.ytimg.com/vi/R6T4CFtS22k/sddefault.jpg', self::getOgpImageUrl($htmlString));
    }

    public function testRenderContentContainsStructuredData(): void {
        global $config;

        $controller = self::createContentController();

        // Content with structured data specified
        $html = self::createSimpleXmlElement($controller->render('/test-data/stuctured-data'));
        $scripts = $html->xpath('//default:body//default:script[@type="application/ld+json"]');
        self::assertEquals(1, count($scripts));
        $json = json_decode((string)$scripts[0]);
        self::assertEquals('BlogPosting', $json->{'@type'});
        self::assertEquals('http://example.com/image.png', $json->image->url);

        // Content with structured data specified, render with configuration
        $config['common_contents_structured_data_types'] = [ 'BlogPosting' ];
        $controller = self::createContentControllerWithConfig($config);
        $html = self::createSimpleXmlElement($controller->render('/test-data/stuctured-data'));
        $scripts = $html->xpath('//default:body//default:script[@type="application/ld+json"]');
        self::assertEquals(1, count($scripts));
        $json = json_decode((string)$scripts[0]);
        self::assertEquals('BlogPosting', $json->{'@type'});
        self::assertEquals('http://example.com/image.png', $json->image->url);

        // Content without structured data, render with configuration
        $html = self::createSimpleXmlElement($controller->render('/test-data/representation-image/specification-in-header'));
        $scripts = $html->xpath('//default:body//default:script[@type="application/ld+json"]');
        self::assertEquals(1, count($scripts));
        $json = json_decode((string)$scripts[0]);
        self::assertEquals('BlogPosting', $json->{'@type'});
        self::assertEquals('https://i.ytimg.com/vi/uacjJ4_dwrE/sddefault.jpg', $json->image->url);
    }

    public function testRenderGoogleCustomSearchEngineScript(): void {
        global $config, $env;

        $controller = self::createContentController();

        // Without Google custom search engine
        $html = self::createSimpleXmlElement($controller->render('/index'));
        self::assertEquals(0, count($html->xpath('//default:script[@class="google-cse"]')));

        // With Google custom search engine
        $config['google_custom_search_engine_id'] = 'cse id';
        $controller = self::createContentControllerWith($config, $env);
        $html = self::createSimpleXmlElement($controller->render('/index'));
        self::assertEquals(1, count($html->xpath('//default:script[@class="google-cse"]')));
    }

    public function testRenderGoogleAnalyticsTrackingScript(): void {
        global $config, $env;

        $controller = self::createContentController();

        // Without Google Analytics tracking
        $html = self::createSimpleXmlElement($controller->render('/index'));
        self::assertEquals(0, count($html->xpath('//default:body/default:script[@class="google-analytics"]')));

        // With Google Analytics tracking
        $config['google_analytics_tracking_id'] = 'tracking code';
        $controller = self::createContentControllerWith($config, $env);
        $html = self::createSimpleXmlElement($controller->render('/index'));
        self::assertEquals(2, count($html->xpath('//default:body/default:script[@class="google-analytics"]')));
    }

    public function testRenderGoogleAdSenseAutomaticAdScript(): void {
        global $config, $env;

        $controller = self::createContentController();

        // Without Google AdSense automatic ads
        $html = self::createSimpleXmlElement($controller->render('/index'));
        self::assertEquals(0, count($html->xpath('//default:head/default:script[@class="google-adsense-automatic-ads"]')));

        // Without Google AdSense automatic ads
        $config['google_adsense_publisher_id'] = 'publisher id';
        $controller = self::createContentControllerWith($config, $env);
        $html = self::createSimpleXmlElement($controller->render('/index'));
        self::assertEquals(2, count($html->xpath('//default:head/default:script[@class="google-adsense-automatic-ads"]')));
    }
}