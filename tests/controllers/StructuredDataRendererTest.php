<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../testEnv.php';
require_once __DIR__ . '/../testConfig.php';

require_once __DIR__ . '/../../app/controllers/TemplateProcessing.php';

final class StructuredDataRendererTest extends TestCase
{
    //
    // Utilities
    //

    private static function createStructuredDataRenderer(array $templateVars): StructuredDataRenderer {
        global $config, $env;

        $env['root_path'] = realpath(__DIR__ . '/../../app/');
        return new StructuredDataRenderer($config, $env, $templateVars);
    }

    //
    // Test cases
    //

    public function testRender() {
        $templateVars = [
            'theme_path' => '/views/themes/default',
            'title' => 'Blog posting title',
            'description' => 'Description of blog posting',
            'author' => 'Author name',
            'site_author' => 'Site author name',
            'path' => '/entity',
            'image_url' => 'http://www.example.com/blot-post-image.png',
            'create_time' => new DateTime('2018-12-24'),
            'update_time' => new DateTime('2018-12-25')
        ];
        $structuredDataVars = [
            'type' => 'BlogPosting'
        ];
        $renderer = self::createStructuredDataRenderer($templateVars);
        $actual = json_decode($renderer->render($structuredDataVars));
        $expect = <<<'EOD'
{
  "@context": "http://schema.org",
  "@type": "BlogPosting",
  "description": "Description of blog posting",
  "author": "Author name",
  "datePublished": "2018-12-24",
  "dateModified": "2018-12-25",
  "headline": "Blog posting title",
  "mainEntityOfPage": {
    "@type": "WebPage"
  },
  "publisher": {
    "@type": "Organization",
    "name": "Site author name",
    "logo": {
      "@type": "ImageObject",
      "url": "http://example.com/image.png"
    }
  },
  "image": {
    "@type": "ImageObject",
    "url": "http://www.example.com/blot-post-image.png"
  }
}
EOD;
        self::assertEquals(json_decode($expect), $actual);
    }
}