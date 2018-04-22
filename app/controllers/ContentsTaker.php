<?php

class TakeArgs {
    public $path;
    public $params;
    public $page_index = 0;
}

/**
 * Contents taker
 */
abstract class ContentsTaker {
    private static $takers = null;

    /*
     * Get instance map of contents taker
     * 
     * @param ContentsProvider $provider contents provider
     * @param int $contents_per_page contents count per page
     * @return array instance map of contents taker
     */
    public static function getTakers(ContentsProvider $provider, int $contents_per_page): array {
        if (is_null(self::$takers) !== false) {
            self::$takers = array(
                'recent-publish' => new RecentPublishContentsTaker($provider, $contents_per_page),
                'recent-update' => new RecentUpdateContentsTaker($provider, $contents_per_page),
                'descendants' => new DecendantsTaker($provider, $contents_per_page),
                'tagged-contents' => new TaggedContentsTaker($provider, $contents_per_page),
                'following' => new FollowingContentsTaker($provider, $contents_per_page),
                'all' => new AllContentsTaker($provider, $contents_per_page)
            );
        }
        return self::$takers;
    }

    /*
     * Is parameter empty?
     * 
     * @param array $params parameters
     * @return bool true if empty
     */
    protected static function isEmptyParameter(array $params = null): bool {
        return is_null($params) !== false || count($params) === 0;
    }

    /*
     * Create default take arguments
     * 
     * @param string $path path
     * @param array $params parameters
     * @param int $page_index page index
     * @return TakeArgs instance of TakeArgs
     */
    protected static function createDefaultTakeArgsInstance(string $path, array $params = null, int $page_index): TakeArgs {
        $args = new TakeArgs();
        $args->path = $path;
        $args->params = $params;
        $args->page_index = $page_index;
        return $args;
    }

    protected $provider;
    protected $contents_per_page;

    /**
     * Constructor
     * 
     * @param ContentsProvider $provider contents provider
     * @param int $contents_per_page contents count per page
     */
    public function __construct(ContentsProvider $provider, int $contents_per_page) {
        $this->provider = $provider;
        $this->contents_per_page = $contents_per_page;
    }

    /*
     * Validate parameter
     * 
     * @param array $params parameters
     * @return bool validation result
     */
    abstract public function isValidParam(array $params = null): bool;

    /*
     * Create take arguments
     * 
     * @param string $path path
     * @param array $params parameters
     * @param int $page_index page index
     * @return TakeArgs instance of TakeArgs
     */
    abstract public function createTakeArgs(string $path, array $params = null, int $page_index): TakeArgs;

    /*
     * Take contents
     * 
     * @param TakeArgs $args take arguments
     * @param string& $set_var_name set variable name
     * @param string& $target_text target text
     * @param array& $twig_vars twig variables
     * @return contents
     */
    abstract public function take(TakeArgs $args, string& $set_var_name, string& $target_text, array& $twig_vars = null);
}

/**
 * Recent publish contents taker
 */
class RecentPublishContentsTaker extends ContentsTaker {
    /*
     * Validate parameter
     * 
     * @param array $params parameters
     * @return bool validation result
     */
    public function isValidParam(array $params = null): bool {
        return true;
    }

    /*
     * Create take arguments
     * 
     * @param string $path path
     * @param array $params parameters
     * @param int $page_index page index
     * @return TakeArgs instance of TakeArgs
     */
    public function createTakeArgs(string $path, array $params = null, int $page_index): TakeArgs {
        return ContentsTaker::createDefaultTakeArgsInstance($path, $params, $page_index);
    }

    /*
     * Take contents
     * 
     * @param TakeArgs $args take arguments
     * @param string& $set_var_name set variable name
     * @param string& $target_text target text
     * @param array& $twig_vars twig variables
     * @return contents
     */
    public function take(TakeArgs $args, string& $set_var_name, string& $target_text, array& $twig_vars = null) {
        return $this->provider->getRecentPublishContents($this->contents_per_page, $args->page_index);
    }
}

/**
 * Recent update contents taker
 */
class RecentUpdateContentsTaker extends ContentsTaker {
    /*
     * Validate parameter
     * 
     * @param array $params parameters
     * @return bool validation result
     */
    public function isValidParam(array $params = null): bool {
        return true;
    }

    /*
     * Create take arguments
     * 
     * @param string $path path
     * @param array $params parameters
     * @param int $page_index page index
     * @return TakeArgs instance of TakeArgs
     */
    public function createTakeArgs(string $path, array $params = null, int $page_index): TakeArgs {
        return ContentsTaker::createDefaultTakeArgsInstance($path, $params, $page_index);
    }

    /*
     * Take contents
     * 
     * @param TakeArgs $args take arguments
     * @param string& $set_var_name set variable name
     * @param string& $target_text target text
     * @param array& $twig_vars twig variables
     * @return contents
     */
    public function take(TakeArgs $args, string& $set_var_name, string& $target_text, array& $twig_vars = null) {
        return $this->provider->getRecentUpdateContents($this->contents_per_page, $args->page_index);
    }
}

/**
 * Decendants taker
 */
class DecendantsTaker extends ContentsTaker {
    /*
     * Validate parameter
     * 
     * @param array $params parameters
     * @return bool validation result
     */
    public function isValidParam(array $params = null): bool {
        return true;
    }

    /*
     * Create take arguments
     * 
     * @param string $path path
     * @param array $params parameters
     * @param int $page_index page index
     * @return TakeArgs instance of TakeArgs
     */
    public function createTakeArgs(string $path, array $params = null, int $page_index): TakeArgs {
        return ContentsTaker::createDefaultTakeArgsInstance($path, $params, $page_index);
    }
    /*
     * Take contents
     * 
     * @param TakeArgs $args take arguments
     * @param string& $set_var_name set variable name
     * @param string& $target_text target text
     * @param array& $twig_vars twig variables
     * @return contents
     */
    public function take(TakeArgs $args, string& $set_var_name, string& $target_text, array& $twig_vars = null) {
        return $this->provider->getDescendantContentsOf($args->path, $this->contents_per_page, $args->page_index);
    }
}

/**
 * Tagged contents taker
 */
class TaggedContentsTaker extends ContentsTaker {
    /*
     * Validate parameter
     * 
     * @param array $params parameters
     * @return bool validation result
     */
    public function isValidParam(array $params = null): bool {
        if (is_null($params) !== false) {
            return true;
        }

        return (count($params) === 0 || (count($params) === 1 && $params[0] !== '' && $this->provider->hasTag($params[0]) !== false));
    }

    /*
     * Create take arguments
     * 
     * @param string $path path
     * @param array $params parameters
     * @param int $page_index page index
     * @return TakeArgs instance of TakeArgs
     */
    public function createTakeArgs(string $path, array $params = null, int $page_index): TakeArgs {
        return ContentsTaker::createDefaultTakeArgsInstance($path, $params, $page_index);
    }

    /*
     * Take contents
     * 
     * @param TakeArgs $args take arguments
     * @param string& $set_var_name set variable name
     * @param string& $target_text target text
     * @param array& $twig_vars twig variables
     * @return contents
     */
    public function take(TakeArgs $args, string& $set_var_name, string& $target_text, array& $twig_vars = null) {
        if (0 < count($args->params)) {
            $twig_vars['title'] = $twig_vars['title'] . ': ' . $args->params[0];
            return $this->provider->getTaggedContents($args->params[0], $this->contents_per_page, $args->page_index);
        }
        else {
            $set_var_name = 'tag_set';
            return $this->provider->getTagSet();
        }
    }
}

/**
 * Following contents taker
 */
class FollowingContentsTaker extends ContentsTaker {
    /*
     * Validate parameter
     * 
     * @param array $params parameters
     * @return bool validation result
     */
    public function isValidParam(array $params = null): bool {
        if (is_null($params) !== false) {
            return false;
        }

        return 0 < count($params);
    }

    /*
     * Create take arguments
     * 
     * @param string $path path
     * @param array $params parameters
     * @param int $page_index page index
     * @return TakeArgs instance of TakeArgs
     */
    public function createTakeArgs(string $path, array $params = null, int $page_index): TakeArgs {
        $args = new TakeArgs();
        $args->page_index = intval(array_shift($params));
        $args->path = '/' . implode('/', $params);
        $args->params = $params;
        return $args;
    }

    /*
     * Take contents
     * 
     * @param TakeArgs $args take arguments
     * @param string& $set_var_name set variable name
     * @param string& $target_text target text
     * @param array& $twig_vars twig variables
     * @return contents
     */
    public function take(TakeArgs $args, string& $set_var_name, string& $target_text, array& $twig_vars = null) {
        $following_content = Controller::getContent($this->provider, $args->path, $args->params);
        if (is_null($following_content) !== false) {
            throw new Exception('Unknown following content: ' . $args->path);
        }
        $target_type = $following_content->target->content->getTarget();
        if ($target_type === 'following') {
            throw new Exception('Recursive following');
        }
        $takers = ContentsTaker::getTakers($this->provider, $this->contents_per_page);
        if (array_key_exists($target_type, $takers) === false) {
            throw new Exception('Unknown target: ' . $target_type);
        }
        if ($following_content->target->content->hasTargetText()) {
            $target_text = $following_content->target->content->getTargetText();
        }

        $dummy_set_var_name = 'main_contents';
        $dummy_target_text = '';
        $following_args = new TakeArgs();
        $following_args->path = $args->path;
        $following_args->params = $args->params;
        $following_args->page_index = $args->page_index;

        return $takers[$target_type]->take($following_args, $dummy_set_var_name, $dummy_target_text, $twig_vars);
    }
}

/**
 * All contents taker
 */
class AllContentsTaker extends ContentsTaker {
    /*
     * Validate parameter
     * 
     * @param array $params parameters
     * @return bool validation result
     */
    public function isValidParam(array $params = null): bool {
        return true;
    }

    /*
     * Create take arguments
     * 
     * @param string $path path
     * @param array $params parameters
     * @param int $page_index page index
     * @return TakeArgs instance of TakeArgs
     */
    public function createTakeArgs(string $path, array $params = null, int $page_index): TakeArgs {
        return ContentsTaker::createDefaultTakeArgsInstance($path, $params, $page_index);
    }

    /*
     * Take contents
     * 
     * @param TakeArgs $args take arguments
     * @param string& $set_var_name set variable name
     * @param string& $target_text target text
     * @param array& $twig_vars twig variables
     * @return contents
     */
    public function take(TakeArgs $args, string& $set_var_name, string& $target_text, array& $twig_vars = null) {
        $part = new PartOfContent();
        $part->part = $this->provider->getListUpContents();
        return $part;
    }
}
?>