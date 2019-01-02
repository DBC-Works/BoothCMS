<?php
/**
 * Controller
 *
 * @copyright D.B.C.
 * @license https://opensource.org/licenses/mit-license.html MIT License
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../models/ContentsProvider.php';
require_once __DIR__ . '/ContentsTaker.php';
include_once __DIR__ . '/TemplateProcessing.php';

/**
 * Subset info
 */
final class SubsetInfo
{
    public $type;
    public $target_text;
    public $subset;
}

/**
 * Controller
 */
final class Controller
{
    use TemplateProcessing;

    /*
     * Get target content
     * 
     * @param ContentsProvider $provider contents provider
     * @param string $path path
     * @param array& $params parameters
     * @return TargetContainer | null target content
     */
    public static function getContent(ContentsProvider $provider, string $path, array& $params): ?TargetContainer {
        $content_path = $path;

        while ($provider->hasContent($content_path) === false) {
            $separator = mb_strrpos($content_path, '/');
            if ($separator === false || $separator < 1) {
                break;
            }
            $param = mb_substr($content_path, $separator + 1);
            array_unshift($params, $param);
            $content_path = mb_substr($content_path, 0, $separator);
        }

        if ($provider->hasContent($content_path) === false) {
            $last_char_pos = mb_strlen($path) - 1;
            if ($path[$last_char_pos] === '/') {
                if (0 < $last_char_pos) {
                    $content_path = mb_substr($path, 0, $last_char_pos);
                }
                if ($provider->hasContent($content_path) === false) {
                    $content_path = $path . 'index';
                }
            }
            else {
                $content_path = $path . '/index';
            }
        }

        return $provider->hasContent($content_path)
                ? $provider->getContent($content_path)
                : null;
    }

    /** 
     * @var ContentsProvider
     */
    private $provider;

    /**
     * @var array
     */
    private $config;

    /**
     * @var array
     */
    private $env;

    /**
     * @var array
     */
    private $twig_vars;

    /**
     * @var array
     */
    private $takers;

    /**
     * Constructor
     * 
     * @param ContentsProvider $provider contents provider
     * @param array $config site config
     * @param array $env server environment information
     */
    public function __construct(ContentsProvider $provider, array $config, array $env) {
        $this->provider = $provider;
        $this->config = $config;
        $this->env = $env;

        $this->twig_vars = null;
    }

    /**
     * Render path contents
     * 
     * @param string $path target path
     * @return string
     */
    public function render(string $path): string {
        assert($path !== '' && $path[0] === '/');

        if (is_null($this->config['timezone']) === false) {
            date_default_timezone_set($this->config['timezone']);
        }
        $this->takers = ContentsTaker::getTakers($this->provider, $this->getContentCountPerPage());
        $this->twig_vars = $this->createBasicTwigArgs($path);
        $this->twig_vars['latest_content_date_and_time'] = $this->provider->getLatestContentDateAndTime();
        $this->twig_vars['oldest_content_date_and_time'] = $this->provider->getOldestContentDateAndTime();

        $params = [];
        $info = self::getContent($this->provider, $path, $params);

        $target_contents = '';
        $taker = null;
        $content_path = $path;
        if (is_null($info) === false) {
            $content_path = $info->target->path;
            if ($info->target->content->hasTarget()) {
                $target_contents = $info->target->content->getTarget();
                if (array_key_exists($target_contents, $this->takers) === false) {
                    throw new Exception('Unknown target: ' . $target_contents);
                }
                $taker = $this->takers[$target_contents];
                if ($taker->isValidParam($params) === false) {
                    $info = null;
                    $target_contents = '';
                }
            }
        }
        $as_list = ($target_contents !== '');

        if (is_null($info) !== false) {
            $info = $this->provider->getContent('/404');
            if ($this->config['in_test'] === false && $_SERVER) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
            }
        }

        $this->twig_vars['author'] = $info->target->content->hasAuthor()
                                    ? $info->target->content->getAuthor()
                                    : $this->config['site_author'];
        if ($info->target->content->hasTitle()) {
            $this->twig_vars['title'] = $info->target->content->getTitle($this->getLang());
        }

        $target_text = $info->target->content->hasTargetText()
                        ? $info->target->content->getTargetText()
                        : 'description';
        $this->twig_vars['description'] = self::escapeMustaches($this->getBody($info->target->content, $target_text, $as_list === false));
        if ($this->twig_vars['description'] === '') {
            $this->twig_vars['description'] = self::escapeMustaches($this->config['site_description']);
        }

        $target_to_set = '';
        $this->twig_vars['as_list'] = $as_list;
        if ($as_list) {
            $subset_info = $this->getContentsSubset($content_path, $taker, $params);
            $target_to_set = $subset_info->type;
            $this->twig_vars += $this->makeSubsetInfo($subset_info, $target_text);
        }
        else {
            $this->twig_vars += $this->makeSingleContentInfo($content_path, $target_text, $info);
        }

        $this->setSupportContentsToTwigVars($content_path, $target_contents, $target_to_set, $target_text, $info);

        $template_name = $info->target->content->hasTemplate()
                        ? $info->target->content->getTemplate()
                        : $this->config['default_template'];
        return $this->applyTemplate($template_name, $this->twig_vars);
    }

    /*
     * Get content count per page
     * 
     * @return int content count per page
     */
    private function getContentCountPerPage(): int {
        return intval($this->config['contents_per_page'] ?? 5);
    }

    /*
     * Get max related contents count
     * 
     * @return int max related contents count
     */
    private function getMaxRelatedContentsCount(): int {
        return intval($this->config['max_related_contents_count'] ?? 5);
    }

    /*
     * Get language
     */
    private function getLang(): ?string {
        return $this->env['lang'];
    }

    /*
     * Get body from content
     * 
     * @param Content $content content
     * @param string $target_text target text of body
     * @param bool $avoid_duplication avoid duplication with body
     * @return string
     */
    private function getBody(Content $content, string $target_text, bool $avoid_duplication): string {
        if ($target_text === 'description' && $content->hasDescription()) {
            return $content->getDescription($this->getLang());
        }

        $body = '';
        if ($target_text === 'beginning' || $avoid_duplication) {
            $part = $content->getBeginningOfBody($this->config['excerpt_letter_limit_length'], $this->getLang());
            $body = $part->part;
            if ($body !== '' && $part->hasFollowing) {
                $body = $body . '…';
            }
        }
        else {
            $body = $content->getTranslatedBody($this->getLang());
        }
        return $body;
    }

    /*
     * Translate content to twig variable style
     * 
     * @param string $path path
     * @param Content $content content
     * @param bool $require_body require body
     * @param string $taget_text target text of body
     * @return array translated contents
     */
    private function translateContent(string $path, Content $content, bool $require_body, string $target_text): array {
        $translated = [
            'path' => $path,
            'title' => $content->getTitle($this->getLang()),
            'date' => $content->getDateAndTime(),
            'update' => $content->getLastUpdateTime(),
            'tags' => $content->getTags()
        ];
        if ($content->hasCategory()) {
            $translated['category'] = $content->getCategory();
        }
        if ($require_body) {
            $translated['body'] = self::escapeMustaches($this->getBody($content, $target_text, false));
        }
        return $translated;
    }

    /*
     * Translate contents to twig variable style
     *
     * @param array $contents contents set
     * @param string $target_text target text
     * @return array
     */
    private function translateContents(array $contents, string $target_text): array {
        $translated = [];
        foreach ($contents as $key => $value) {
            $translated[] = $this->translateContent($key, $value, true, $target_text);
        }
        return $translated;
    }

    /**
     * Get contents subsets
     * 
     * @param string $path target path
     * @param ContentsTaker $taker contents taker
     * @param array|null $params parameters
     * @return SubsetInfo
     */
    private function getContentsSubset(
        string $path,
        ContentsTaker $taker,
        array $params = null
    ): SubsetInfo {
        assert($path !== '');

        $set_var_name = 'main_contents';
        $target_text = '';
        $info = new SubsetInfo();
        $info->subset = $taker->take(
            $taker->createTakeArgs($path, $params, 0),
            $set_var_name,
            $target_text,
            $this->twig_vars
        );
        $info->type = $set_var_name;
        $info->target_text = $target_text;
        return $info;
    }

    /**
     * Create basic Twig arguments
     * 
     * @param string $path content path
     */
    private function createBasicTwigArgs(string $path): array {
        assert($path !== '');

        $twig_vars = [
            'site_title' => $this->config['site_title'],
            'site_subtitle' => $this->config['site_subtitle'],
            'site_url' => rtrim($this->config['site_url'], "/\\"),
            'site_rfc4151_id' =>$this->config['site_rfc4151_id'],
            'site_author_twitter' =>$this->config['site_author_twitter'],
            'format_datetime' =>$this->config['format_datetime'],
            'sitemap_changefreq' => $this->config['sitemap_changefreq'],
            'path' => $path,
            'theme_path' => '/views/themes/' . $this->config['theme']
        ];
        if ($this->env['as_develop'] === false) {
            $keys = [
                'google_analytics_tracking_id',
                'google_adsense_publisher_id',
                'google_custom_search_engine_id'
            ];
            foreach ($keys as $key) {
                if (array_key_exists($key, $this->config)
                && is_null($this->config[$key]) === false) {
                    $value = $this->config[$key];
                    if ($value) {
                        $twig_vars[$key] = $value;
                    }
                }
            }
        }
        return $twig_vars;
    }


    /**
     * Get complemented image url
     * 
     * @param string $source_image_url source image url
     * @return string image url
     */
    private function getComplementedImageUrl(?string $source_image_url): string {
            $image_url = $source_image_url;
        if (is_null($image_url) !== false || $image_url === '') {
            $image_url = $this->config['site_image_path'];
        }
        if (is_null($image_url) === false
        && mb_ereg_match('^https?:', $image_url) === false
        && mb_ereg_match('^//', $image_url) === false) {
            if ($image_url[0] !== '/') {
                $image_url = '/' . $image_url;
            }
            $image_url = $this->config['site_url'] . $image_url;
        }
        return $image_url;
    }

    /**
     * Make subset info
     * 
     * @param SubsetInfo $subset_info subset_info
     * @param string $target_text target text
     * @return array subset info
     */
    private function makeSubsetInfo(SubsetInfo $subset_info, string $target_text): array {
        $vars = [
            'image_url' => $this->getComplementedImageUrl($this->config['site_image_path'])
        ];

        if ($subset_info->type === 'main_contents') {
            if (count($subset_info->subset->part) === 0) {
                $vars['update_time'] = new DateTime('now');
                $vars[$subset_info->type] = [];
            }
            else {
                $vars['update_time'] = current($subset_info->subset->part)->getLastUpdateTime();
                $strict_target_text = $subset_info->target_text !== ''
                                    ? $subset_info->target_text
                                    : $target_text;
                $vars[$subset_info->type] = $this->translateContents($subset_info->subset->part, $strict_target_text);
            }
            $vars['has_following'] = $subset_info->subset->hasFollowing;
        }
        elseif ($subset_info->type === 'tag_set') {
            $vars[$subset_info->type] = $subset_info->subset;
            $vars['has_following'] = false;
        }
        else {
            throw new Exception('Not implemented variable name: ' . $subset_info->type);
        }
        return $vars;
    }

    /**
     * Set support contents info to Twig vars
     * 
     * @param string $content_path content path
     * @param string $target_contents target contents
     * @param string $target_to_set main target
     * @param string $target_text target text
     * @param TargetContainer $info content information to set
     */
    private function setSupportContentsToTwigVars(string $content_path, string $target_contents, string $target_to_set, string $target_text, TargetContainer $info): void {
        assert($content_path !== '');
        assert($target_text !== '');

        $support_target = $info->target->content->hasSupportTarget()
                        ? $info->target->content->getSupportTarget()
                        : $this->config['default_support_contents'];
        if ($support_target === 'unused') {
            return;
        }

        if ($target_contents === $support_target) {
            $this->twig_vars['support_contents'] = $this->twig_vars[$target_to_set];
        }
        else {
            $support_info = $this->getContentsSubset(
                $content_path,
                $this->takers[$support_target],
                null
            );
            if ($support_info->type !== 'main_contents') {
                throw new Exception('Not usable for support content var name: ' . $support_info->type);
            }
            $this->twig_vars['support_contents'] = $this->translateContents($support_info->subset->part, $target_text);
        }
    }

    /**
     * Create link info
     * 
     * @param ContentInfo $info content info
     * @return array link info
     */
    private function createLinkInfo(ContentInfo $info): array {
        return [
            'path' => $info->path,
            'title' => $info->content->getTitle($this->getLang())
        ];
    }

    /**
     * Make structured data
     * 
     * @param Content $content content
     * @param array $twig_vars twig vars
     * @param string $image_url image url
     * @return array Structured data collection
     */
    private function makeStructuredData(Content $content, array $twig_vars, string $image_url): array {
        $structured_data = [];

        $renderer = new StructuredDataRenderer($this->config, $this->env, $twig_vars);
        $types = [];
        foreach ($content->getStructuredDataInfo() as $data) {
            $translated = [ 'image_url' => $image_url ];
            foreach ($data as $data_key => $data_value) {
                $snake_case_key = ltrim(mb_strtolower(mb_ereg_replace('[A-Z]', '_\0', $data_key)), '_');
                $translated[$snake_case_key] = $data_value;
            }
            if (array_key_exists($translated['type'], $types) === false) {
                $structured_data[] = "<script type=\"application/ld+json\">\n"
                . $renderer->render($translated)
                . "\n</script>\n";
                $types[] = $translated['type'];
            }
        }
        if (array_key_exists('common_contents_structured_data_types', $this->config) !== false) {
            $common_types = $this->config['common_contents_structured_data_types'];
            foreach ($common_types as $type) {
                if (in_array($type, $types) === false) {
                    $structured_data[] = "<script type=\"application/ld+json\">\n"
                    . $renderer->render([
                        'type' => $type,
                        'image_url' => $image_url
                    ])
                    . "\n</script>";
                }
            }
        }

        return $structured_data;
    }

    /**
     * Make single content info
     * 
     * @param string $content_path content path
     * @param string $target_text target text
     * @param TargetContainer $info content information to set
     * @return array single content info
     */
    private function makeSingleContentInfo(string $content_path, string $target_text, TargetContainer $info): array {
        assert($content_path !== '');
        assert($target_text !== '');

        $translated = $this->translateContent($content_path, $info->target->content, false, $target_text);
        $translated['body'] = self::escapeMustaches($info->target->content->getTranslatedBody($this->getLang()));
        if (is_null($info->prev) === false) {
            $translated['prev'] = $this->createLinkInfo($info->prev);
        }
        if (is_null($info->next) === false) {
            $translated['next'] = $this->createLinkInfo($info->next);
        }

        $related_contents = $this->provider->getRelatedContentsOf($info->target->content, $this->getMaxRelatedContentsCount());
        $related_contents_info = [];
        foreach ($related_contents as $related_content) {
            $related_contents_info[] = $this->createLinkInfo($related_content);
        }

        $image_url = $this->getComplementedImageUrl($info->target->content->getRepresentationImageSource());
        return [
            'create_time' => $info->target->content->getDateAndTime(),
            'update_time' => $info->target->content->getLastUpdateTime(),
            'tags' => $info->target->content->getTags(),
            'main_contents' => [ $translated ],
            'image_url' => $image_url,
            'exclude_from_list' => ($info->target->content->canListUp() === false),
            'related_contents' => $related_contents_info,
            'structured_data' => $this->makeStructuredData($info->target->content, $this->twig_vars, $image_url)
        ];
    }

    /**
     * Apply template
     * 
     * @param string $template_name template name
     * @return string render result
     */
    private function applyTemplate(string $template_name, array $twig_vars): string {
        assert(0 < count($twig_vars));
        assert($template_name !== '');

        $loader = $this->createTwigLoader($template_name, $this->env['root_path'], $twig_vars['theme_path']);
        $options = $this->createTwigOptions($this->config, $this->env);
        $twig = $this->createTwig($loader, $options);
        return $twig->render($template_name, $twig_vars);
    }
}
