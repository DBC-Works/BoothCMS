<?php
/**
 * Contents provider classes
 *
 * @copyright D.B.C.
 * @license https://opensource.org/licenses/mit-license.html MIT License
 */

 require_once __DIR__ . '/Content.php';

/**
 * ContentInfo
 */
final class ContentInfo
{
    public $path;
    public $content;

    /**
     * Constructor
     *
     * @param string $path path
     * @param Content $content content
     */
    public function __construct(string $path, Content $content) {
        assert($path !== '');

        $this->path = $path;
        $this->content = $content;
    }
}

/**
 * TargetContainer
 */
final class TargetContainer
{
    public $prev;
    public $target;
    public $next;
}

/**
 * ContentsProvider
 */
final class ContentsProvider
{
    /**
     * Load contents
     *
     * @param string $content_dir_path contents directory path
     * @param string $child_path child directory path
     * @param DateTime $now current time
     * @return array
     */
    private static function load(string $contents_dir_path, string $child_path, DateTime $now): array {
        assert(file_exists($contents_dir_path) && is_dir($contents_dir_path));

        $contents = [];

        $dir_path = $contents_dir_path . $child_path;
        foreach (scandir($dir_path) as $entry) {
            $entry_path = $dir_path . '/' . $entry;
            if (is_dir($entry_path)) {
                if ($entry !== '.' && $entry !== '..') {
                    $child_contents = self::load($contents_dir_path, $child_path . '/' . $entry, $now);
                    $contents = array_merge($contents, $child_contents);
                }
            }
            else {
                $ext = mb_strtolower(pathinfo($entry, PATHINFO_EXTENSION));
                if ($ext === 'md' || $ext === 'yaml' || $ext === 'yml') {
                    $content = Content::load($entry_path);
                    if ($content->released($now) !== false) {
                        $contents[$child_path . '/' . pathinfo($entry, PATHINFO_FILENAME)] = $content;
                    }
                }
            }
        }

        return $contents;
    }

    /**
     * Create contents subset info
     *
     * @param array $contents contents set
     * @param int $max_count max count of subset contents
     * @param int $index target index
     * @return PartOfContent
     */
    private static function createSubsetInfo(array $contents, int $max_count, int $index): PartOfContent {
        assert(0 < $max_count);
        assert(0 <= $index);

        $chunk = array_chunk($contents, $max_count, true);
        $part = new PartOfContent();
        $part->part = $chunk[$index];
        $part->hasFollowing = ($index + 1) < count($chunk);
        return $part;
    }

    private $contents;

    /**
     * Constructor
     *
     * @param string $content_dir_path contents directory path
     * @param DateTime $now current time
     */
    public function __construct(string $contents_dir_path, DateTime $now) {
        assert(is_null($content_dir_path) === false && file_exists($content_dir_path) && is_dir($content_dir_path));

        $this->contents = self::load(rtrim($contents_dir_path, '/\\'), '', $now);
        uasort($this->contents, function($lhs, $rhs) {
            if ($lhs->getDateAndTime() === $rhs->getDateAndTime()) {
                return 0;
            }
            return ($lhs->getDateAndTime() < $rhs->getDateAndTime()) ? 1 : -1;
        });
    }

    /**
     * Get update time
     *
     * @return DateTime
     */
    public function getUpdateTime(): DateTime {
        return current($this->getRecentUpdateContents(1, 0)->part)->getLastUpdateTime();
    }

    /**
     * List up contents
     * 
     * @return array
     */
    public function getListUpContents(): array {
        return array_filter($this->contents, function($content) {
            return $content->canListUp();
        });
    }

    /**
     * Get latest content date and time
     *
     * @return DateTime
     */
    public function getLatestContentDateAndTime(): DateTime {
        $listUpContents = $this->getListUpContents();
        return current($listUpContents)->getDateAndTime();
    }

    /**
     * Get oldest content date and time
     *
     * @return DateTime
     */
    public function getOldestContentDateAndTime(): DateTime {
        $listUpContents = $this->getListUpContents();
        return end($listUpContents)->getDateAndTime();
    }

    /**
     * Get recent publish contents
     *
     * @param int $max_count max count
     * @param int $page_index page index
     * @return PartOfContent
     */
    public function getRecentPublishContents(int $max_count, int $page_index): PartOfContent {
        assert(0 < $max_count);
        assert(0 <= $page_index);

        return self::createSubsetInfo($this->getListUpContents(), $max_count, $page_index);
    }

    /**
     * Get recent update contents
     *
     * @param int $max_count max count
     * @param int $page_index page index
     * @return PartOfContent
     */
    public function getRecentUpdateContents(int $max_count, int $page_index): PartOfContent {
        assert(0 < $max_count);
        assert(0 <= $page_index);

        $contents = $this->getListUpContents();
        uasort($contents, function($lhs, $rhs) {
            if ($lhs->getLastUpdateTime() === $rhs->getLastUpdateTime()) {
                if ($lhs->getDateAndTime() === $rhs->getDateAndTime()) {
                    return 0;
                }
                return ($lhs->getDateAndTime() < $rhs->getDateAndTime()) ? 1 : -1;
            }
            return ($lhs->getLastUpdateTime() < $rhs->getLastUpdateTime()) ? 1 : -1;
        });
        return self::createSubsetInfo($contents, $max_count, $page_index);
    }

    /**
     * Get decendant contents of specified path
     * 
     * @param string $path target path
     * @param int $max_count max count
     * @param int $page_index page index
     * @return PartOfContent
     */
    public function getDescendantContentsOf(string $path, int $max_count, int $page_index): PartOfContent {
        assert($path !== '' && $path[0] === '/');
        assert(0 < $max_count);
        assert(0 <= $page_index);

        $base_path = $path;
        $trail_delimiter = mb_strrpos($base_path, '/');
        if ($trail_delimiter !== mb_strlen($base_path)) {
            $base_path = mb_substr($path, 0, $trail_delimiter + 1);
        }
        $descendants = [];
        foreach ($this->getListUpContents() as $key => $value) {
            if (mb_strpos($key, $base_path, 0) === 0) {
                $descendants[$key] = $value;
            }
        }
        return self::createSubsetInfo($descendants, $max_count, $page_index);
    }

    /**
     * Get languages
     * 
     * @return array array of ISO 639-1 code
     */
    public function getLanguages(): array {
        $languages = [];
        foreach ($this->getListUpContents() as $key => $value) {
            foreach ($value->getLanguages() as $lang) {
                if (array_key_exists($lang, $languages) === false) {
                    $languages[$lang] = 0;
                }
                $languages[$lang] = $languages[$lang] + 1;
            }
        }
        uasort($languages, function($lhs, $rhs) {
            return $lhs - $rhs;
        });
        return array_keys($languages);
    }

    /**
     * Get tag set
     * 
     * @return array
     */
    public function getTagSet(): array {
        $tags = [];
        foreach ($this->getListUpContents() as $key => $value) {
            foreach ($value->getTags() as $tag) {
                if (array_key_exists($tag, $tags) === false) {
                    $tags[$tag] = [];
                }
                $tags[$tag][] = $key;
            }
        }
        uasort($tags, function($lhs, $rhs) {
            return (count($lhs) < count($rhs) ? 1 : -1);
        });
        return $tags;
    }

    /**
     * Has tag?
     * 
     * @param string $tag tag name
     * @return bool
     */
    public function hasTag(string $tag): bool {
        assert($tag !== '');
        return array_key_exists($tag, $this->getTagSet());
    }

    /**
     * Get tagged contents
     * 
     * @param string $tag specified tag
     * @param int $max_count max count
     * @param int $page_index page index
     * @return array
     */
    public function getTaggedContents(string $tag, int $max_count, int $page_index): PartOfContent {
        assert($tag !== '');
        assert(0 < $max_count);
        assert(0 <= $page_index);

        $tagged_contents = array_filter($this->getListUpContents(), function($content) use ($tag) {
            return $content->hasTag($tag);
        });

        $part = null;
        if (count($tagged_contents) === 0) {
            $part = new PartOfContent;
            $part->part = [];
        }
        else {
            $part = self::createSubsetInfo($tagged_contents, $max_count, $page_index);
        }
        return $part;
    }

    /**
     * Get related contents
     * 
     * @param Content $contet content to get related contents
     * @param int $max_count max count
     * @return array
     */
    public function getRelatedContentsOf(Content $content, int $max_count): array {
        assert($content !== '');
        assert(0 < $max_count);

        $tags = $content->getTags();
        $tag_count = count($tags);
        if ($tag_count === 0) {
            return [];
        }

        $match_contents = [];
        $inclusion_contents = [];
        $partial_match_contents = [];

        $contents = array_filter($this->getListUpContents(), function($content) {
            return 0 < count($content->getTags());
        });
        foreach ($contents as $key => $value) {
            if ($value !== $content) {
                $comparison_tags = $value->getTags();
                $diff = array_diff($tags, $comparison_tags);
                $diff_count = count($diff);
                if ($diff_count === 0) {
                    if ($tag_count === count($comparison_tags)) {
                        $match_contents[$key] = $value;
                        if ($max_count <= count($match_contents)) {
                            break;
                        }
                    }
                    else {
                        $inclusion_contents[$key] = $value;
                    }
                }
                elseif ($diff_count < $tag_count) {
                    $partial_match_contents[$key] = $value;
                }
            }
        }
        uasort($inclusion_contents, function($lhs, $rhs) {
            return count($rhs->getTags()) - count($lhs->getTags());
        });
        uasort($partial_match_contents, function($lhs, $rhs) {
            return ($tag_count - count($rhs->getTags())) - ($tag_count - count($lhs->getTags()));
        });
        $related_contents = array_merge($match_contents, $inclusion_contents, $partial_match_contents);
        $related_contents = array_slice($related_contents, 0, $max_count);
        $related_contents_info = [];
        foreach ($related_contents as $key => $value) {
            $related_contents_info[] = new ContentInfo($key, $value);
        }
        return $related_contents_info;
    }

    /**
     * Has content?
     *
     * @param string $path path
     * @return bool
     */
    public function hasContent(string $path): bool {
        return array_key_exists($path, $this->contents);
    }

    /**
     * Get content
     *
     * @param string $path path
     * @return TargetContainer
     */
    public function getContent(string $path): TargetContainer {
        $content = $this->contents[$path];
        $target = new TargetContainer();
        if (is_null($content) === false) {
            $target->target = new ContentInfo($path, $content);

            if ($content->canListUp()) {
                $listUpContents = $this->getListUpContents();
                $keys = array_keys($listUpContents);
                $index = array_search($path, $keys);
                if ($index !== false) {
                    if (0 < $index) {
                        $next_key = $keys[$index - 1];
                        $target->next = new ContentInfo($next_key, $listUpContents[$next_key]);
                    }
                    if ($index < (count($listUpContents) - 1)) {
                        $prev_key = $keys[$index + 1];
                        $target->prev = new ContentInfo($prev_key, $listUpContents[$prev_key]);
                    }
                }
            }
        }
        return $target;
    }
}
