<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../models/ContentsProvider.php';
require_once __DIR__ . '/ContentsTaker.php';

/**
 * Subset info
 */
class SubsetInfo {
    public $type;
    public $target_text;
    public $subset;
}

/**
 * Controller
 */
class Controller {
    /*
     * Get target content
     * 
     * @param ContentsProvider $provider contents provider
     * @param string $path path
     * @param array& $params parameters
     */
    public static function getContent(ContentsProvider $provider, string $path, array& $params) {
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

    private $provider;
    private $config;
    private $env;

    private $twig_vars;
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

        $params = array();
        $info = Controller::getContent($this->provider, $path, $params);

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
        $this->twig_vars['description'] = $this->getBody($info->target->content, $target_text);
        if ($this->twig_vars['description'] === '') {
            $this->twig_vars['description'] = $this->config['site_description'];
        }

        $target_to_set = '';
        $this->twig_vars['as_list'] = ($target_contents !== '');
        if ($this->twig_vars['as_list']) {
            $subset_info = $this->getContentsSubset($content_path, $taker, $params);
            $target_to_set = $subset_info->type;
            $this->setSubsetInfoToTwigVars($subset_info, $target_text);
        }
        else {
            $this->setSingleContentInfoToTwigVars($content_path, $target_text, $info);
        }

        $this->setSupportContentsToTwigVars($content_path, $target_contents, $target_text, $info);

        $template_name = $info->target->content->hasTemplate()
                        ? $info->target->content->getTemplate()
                        : $this->config['default_template'];
        return $this->applyTemplate($template_name);
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
     * Get language
     */
    private function getLang() {
        return $this->env['lang'];
    }

    /*
     * Get body from content
     * 
     * @param Content $content content
     * @param string $target_text target text of body
     * @return string
     */
    private function getBody(Content $content, string $target_text): string {
        if ($target_text === 'description' && $content->hasDescription()) {
            return $content->getDescription($this->getLang());
        }

        $body = '';
        if ($target_text === 'beginning') {
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
        $translated = array(
            'path' => $path,
            'title' => $content->getTitle($this->getLang()),
            'date' => $content->getDateAndTime(),
            'update' => $content->getLastUpdateTime(),
            'tags' => $content->getTags()
        );
        if ($content->hasCategory()) {
            $translated['category'] = $content->getCategory();
        }
        if ($require_body) {
            $translated['body'] = $this->getBody($content, $target_text);
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
        $translated = array();
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
    private function getContentsSubset(string $path, ContentsTaker $taker, array $params = null): SubsetInfo {
        assert($path !== '');

        $set_var_name = 'main_contents';
        $target_text = '';
        $info = new SubsetInfo();
        $info->subset = $taker->take($taker->createTakeArgs($path, $params, 0),
                                     $set_var_name,
                                     $target_text,
                                     $this->twig_vars);
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

        return array(
            'site_title' => $this->config['site_title'],
            'site_subtitle' => $this->config['site_subtitle'],
            'site_url' => rtrim($this->config['site_url'], "/\\"),
            'site_rfc4151_id' =>$this->config['site_rfc4151_id'],
            'site_author_twitter' =>$this->config['site_author_twitter'],
            'format_datetime' =>$this->config['format_datetime'],
            'sitemap_changefreq' => $this->config['sitemap_changefreq'],
            'path' => $path
        );
    }

    /**
     * Create Twig options
     * 
     * @return array twig options
     */
    private function createTwigOptions(): array {
        return array(
            'autoescape' => false,
            'cache' => $this->config['twig_enable_cache'] ? $this->env['root_path'] . '/cache/twig' : false,
            'debug' => $this->config['twig_enable_debug']
        );
    }

    /**
     * Set subset info to Twig vars
     * 
     * @param SubsetInfo $subset_info subset_info
     * @param string $target_text target text
     */
    private function setSubsetInfoToTwigVars(SubsetInfo $subset_info, string $target_text) {
        $this->twig_vars['image_path'] = $this->config['site_image_path'];

        if ($subset_info->type === 'main_contents') {
            if (count($subset_info->subset->part) === 0) {
                $this->twig_vars['update_time'] = new DateTime('now');
                $this->twig_vars[$subset_info->type] = array();
            }
            else {
                $this->twig_vars['update_time'] = current($subset_info->subset->part)->getLastUpdateTime();
                $strict_target_text = $subset_info->target_text !== ''
                                    ? $subset_info->target_text
                                    : $target_text;
                $this->twig_vars[$subset_info->type] = $this->translateContents($subset_info->subset->part, $strict_target_text);
            }
            $this->twig_vars['has_following'] = $subset_info->subset->hasFollowing;
        }
        else if ($subset_info->type === 'tag_set') {
            $this->twig_vars[$subset_info->type] = $subset_info->subset;
            $this->twig_vars['has_following'] = false;
        }
        else {
            throw new Exception('Not implemented variable name: ' . $subset_info->type);
        }
    }

    /**
     * Set single content info to Twig vars
     * 
     * @param string $content_path content path
     * @param string $target_contents target contents
     * @param string $target_text target text
     * @param TargetContainer $info content information to set
     */
    private function setSupportContentsToTwigVars(string $content_path, string $target_contents, string $target_text, TargetContainer $info) {
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
            $dummy_target_text = '';
            $support_info = $this->getContentsSubset($content_path, $this->takers[$support_target], null, $dummy_target_text);
            if ($support_info->type !== 'main_contents') {
                throw new Exception('Not usable for support content var name: ' . $support_info->type);
            }
            $this->twig_vars['support_contents'] = $this->translateContents($support_info->subset->part, $target_text);
        }
    }

    /**
     * Set single content info to Twig vars
     * 
     * @param string $content_path content path
     * @param string $target_text target text
     * @param TargetContainer $info content information to set
     */
    private function setSingleContentInfoToTwigVars(string $content_path, string $target_text, TargetContainer $info) {
        assert($content_path !== '');
        assert($target_text !== '');

        $this->twig_vars['create_time'] = $info->target->content->getDateAndTime();
        $this->twig_vars['update_time'] = $info->target->content->getLastUpdateTime();
        $this->twig_vars['tags'] = $info->target->content->getTags();
        $translated = $this->translateContent($content_path, $info->target->content, false, $target_text);
        $translated['body'] = $info->target->content->getTranslatedBody($this->getLang());
        if (is_null($info->prev) === false) {
            $translated['prev'] = array(
                'path' => $info->prev->path,
                'title' => $info->prev->content->getTitle($this->getLang())
            );
        }
        if (is_null($info->next) === false) {
            $translated['next'] = array(
                'path' => $info->next->path,
                'title' => $info->next->content->getTitle($this->getLang())
            );
        }
        $this->twig_vars['main_contents'] = array(
            $translated
        );
        $image_path = $info->target->content->getRepresentationImage();
        $this->twig_vars['image_path'] = (is_null($image_path) === false && $image_path !== '')
                                        ? $image_path
                                        : $this->config['site_image_path'];
    }

    /**
     * Apply template
     * 
     * @param string $template_name template name
     * @return string render result
     */
    private function applyTemplate(string $template_name): string {
        assert(0 < count($twig_vars));
        assert($template_name !== '');

        $themes_path = $this->env['root_path'] . '/views/themes/' . $this->config['theme'];
        if (file_exists($themes_path . '/' .$template_name) === false) {
            $themes_path = $this->env['root_path'] . '/views';
            if (file_exists($themes_path . '/' .$template_name) === false) {
                throw new Exception('Template is not exist: ' . $template_name);
            }
        }

        $loader = new Twig_Loader_Filesystem($themes_path);
        $twig = new Twig_Environment($loader, $this->createTwigOptions());
        $twig->addExtension(new Twig_Extension_Debug());
        return $twig->render($template_name, $this->twig_vars);
    }
}
?>