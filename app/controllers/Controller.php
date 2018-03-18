<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../models/ContentsProvider.php';

/**
 * Subset arg
 */
class SubsetArg {
    public $path;
    public $params;
    public $page_index = 0;
}

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
    private $provider;
    private $config;
    private $env;

    private $twig_vars;
    private $target_operator;

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
        $this->target_operator = array(
            'recent-publish' => function(SubsetArg $args, string& $set_var_name, string& $target_text) {
                return $this->provider->getRecentPublishContents($this->getContentCountPerPage(), $args->page_index);
            },
            'recent-update' => function(SubsetArg $args, string& $set_var_name, string& $target_text) {
                return $this->provider->getRecentUpdateContents($this->getContentCountPerPage(), $args->page_index);
            },
            'descendants' => function(SubsetArg $args, string& $set_var_name, string& $target_text) {
                return $this->provider->getDescendantContentsOf($args->path, $this->getContentCountPerPage(), $args->page_index);
            },
            'tagged-contents' => function(SubsetArg $args, string& $set_var_name, string& $target_text) {
                if (0 < count($args->params)) {
                    $this->twig_vars['title'] = $this->twig_vars['title'] . ': ' . $args->params[0];
                    return $this->provider->getTaggedContents($args->params[0], $this->getContentCountPerPage(), $args->page_index);
                }
                else {
                    $set_var_name = 'tag_set';
                    return $this->provider->getTagSet();
                }
            },
            'following' => function(SubsetArg $args, string& $set_var_name, string& $target_text) {
                $following_args = new SubsetArg();
                $following_args->page_index = intval(array_shift($args->params));
                $following_args->path = '/' . implode('/', $args->params);
                $following_args->params = array();

                $following_content = $this->getContent($following_args->path, $following_args->params);
                if (is_null($following_content) !== false) {
                    throw new Exception('Unknown following content: ' . $following_args->path);
                }
                $target_type = $following_content->target->content->getTarget();
                if ($target_type === 'following') {
                    throw new Exception('Recursive following');
                }
                if (array_key_exists($target_type, $this->target_operator) === false) {
                    throw new Exception('Unknown target: ' . $target_type);
                }
                if ($following_content->target->content->hasTargetText()) {
                    $target_text = $following_content->target->content->getTargetText();
                }
                $dummy_set_var_name = 'main_contents';
                $dummy_target_text = '';
                return $this->target_operator[$target_type]($following_args, $dummy_set_var_name, $dummy_target_text);
            },
            'all' => function(SubsetArg $args, string& $set_var_name, string& $target_text) {
                $part = new PartOfContent();
                $part->part = $this->provider->getListUpContents();
                return $part;
            }
        );
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

        $params = array();
        $info = $this->getContent($path, $params);

        $content_path = $path;
        if (is_null($info) === false) {
            $content_path = $info->target->path;
        }
        else {
            $info = $this->provider->getContent('/404');
            if ($_SERVER) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
            }
        }

        $this->twig_vars = $this->createBasicTwigArgs($path);

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

        $target_contents = '';
        $target_to_set = '';
        $this->twig_vars['as_list'] = $info->target->content->hasTarget();
        if ($this->twig_vars['as_list']) {
            $target_contents = $info->target->content->getTarget();
            $subset_info = $this->getContentsSubset($content_path, $target_contents, $params);
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
     * Get target content
     * 
     * @param string $path path
     * @param array& $params paraneters
     */
    private function getContent(string $path, array &$params) {
        $content_path = $path;

        while ($this->provider->hasContent($content_path) === false) {
            $separator = mb_strrpos($content_path, '/');
            if ($separator === false || $separator < 1) {
                break;
            }
            $param = mb_substr($content_path, $separator + 1);
            array_unshift($params, $param);
            $content_path = mb_substr($content_path, 0, $separator);
        }

        if ($this->provider->hasContent($content_path) === false) {
            $last_char_pos = mb_strlen($path) - 1;
            if ($path[$last_char_pos] === '/') {
                if (0 < $last_char_pos) {
                    $content_path = mb_substr($path, 0, $last_char_pos);
                }
                if ($this->provider->hasContent($content_path) === false) {
                    $content_path = $path . 'index';
                }
            }
            else {
                $content_path = $path . '/index';
            }
        }

        return $this->provider->hasContent($content_path)
                ? $this->provider->getContent($content_path)
                : null;
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
     * @param string $target_type target subset type
     * @param array|null $params parameters
     * @return SubsetInfo
     */
    private function getContentsSubset(string $path, string $target_type, $params): SubsetInfo {
        assert($path !== '');
        assert($target_type !== '');

        if (array_key_exists($target_type, $this->target_operator) === false) {
            throw new Exception('Unknown target: ' . $target_type);
        }

        $args = new SubsetArg();
        $args->path = $path;
        $args->params = $params;
        $set_var_name = 'main_contents';
        $target_text = '';
        $subset = $this->target_operator[$target_type]($args, $set_var_name, $target_text);

        $info = new SubsetInfo();
        $info->type = $set_var_name;
        $info->subset = $subset;
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
            $support_info = $this->getContentsSubset($content_path, $support_target, null, $dummy_target_text);
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