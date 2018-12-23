--TEST--
Template variables setting test
--FILE--
<?php
require_once __DIR__ . '/../../app/controllers/TemplateProcessing.php';
require_once __DIR__ . '/../testEnv.php';
require_once __DIR__ . '/../testConfig.php';

$template_vars = [
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
$structured_data_vars = [
  'type' => 'BlogPosting'
];

$renderer = new StructuredDataRenderer($config, $env, $template_vars);
echo '<script type="application/ld+json">';
echo "\n";
echo $renderer->render($structured_data_vars);
echo "\n";
echo '</script>';
echo "\n";
?>
--EXPECT--
<script type="application/ld+json">
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
</script>