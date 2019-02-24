<?php
/**
 * Contents classes
 *
 * @copyright D.B.C.
 * @license https://opensource.org/licenses/mit-license.html MIT License
 */

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * PartOfContent
 */
final class PartOfContent
{
    public $part;
    public $hasFollowing = false;
}

/**
 * Content
 */
final class Content
{
    private static $parser;

    /**
     * Parse headers of Comment style content
     * 
     * @param string $header_text header text
     * @return array headers;
     */
    private static function parseCommentHeader(string $header_text): array {
        $headers = [];
        $header_lines = mb_split("[\r\n]+", mb_substr($header_text, 2));
        foreach ($header_lines as $line) {
            if ($line !== '') {
                $separator = mb_strpos($line, ':');
                if ($separator < 1) {
                    throw new Exception('Invalid header: ' + $line);
                }
                $headers[trim(mb_substr($line, 0, $separator))] = trim(mb_substr($line, $separator + 1));
            }
        }
        return $headers;
    }

    /**
     * Parse body of YAML style content
     * 
     * @param array $parts parts of content
     * @param array|string|null $title title
     * @return array|string body
     */
    private static function parseYamlBody(array $parts, $title) {
        $body = '';
        if (count($parts) === 1) {
            $body = trim($parts[0]);
        }
        elseif (1 < count($parts)){
            $body = [];
            if (is_array($title) !== false) {
                $index = 0;
                foreach ($title as $key => $value) {
                    $body[$key] = trim($parts[$index++]);
                }
            }
            else {
                foreach ($parts as $part) {
                    $body[] = trim($part);
                }
            }
        }
        return $body;
    }

    /**
     * Load content
     *
     * @param string $content_file_path content path
     * @return Content
     */
    public static function load(string $content_file_path): Content {
        assert(file_exists($content_file_path) && is_file($content_dir_path));

        $content = file_get_contents($content_file_path);
        if ($content === false) {
            throw new Exception('Cannot get content: ' + $content_file_path);
        }
        $content = trim($content);

        $headers = null;
        $body = '';
        if (mb_strpos($content, '/*') === 0) {
            // comment style header document
            $parts = explode('*/', $content);
            if (count($parts) !== 2) {
                throw new Exception('Invalid content: ' + $content_file_path);
            }
            $headers = self::parseCommentHeader($parts[0]);
            $body = trim($parts[1]);
        }
        else {
            // YAML style header document
            while (mb_strpos($content, '#') === 0) {
                $content = mb_ereg_replace("^#[^\r\n]*[\r\n]+", '', $content);
            }
            $delimiter = self::detectDelimiterString($content);
            if ($delimiter !== null) {
                $parts = mb_split(self::detectDelimiterString($content), $content);
                $headers = Spyc::YAMLLoadString(array_shift($parts));
                $body = self::parseYamlBody($parts, array_key_exists('Title', $headers) ? $headers['Title'] : null);
            }
            else {
                $headers = Spyc::YAMLLoadString($content);
                $body = '';
            }
        }

        if (array_key_exists('Tags', $headers)) {
            $tags = $headers['Tags'];
            if (is_string($tags)) {
                $headers['Tags'] = explode(',', $tags);
            }
        }
        else {
            $headers['Tags'] = [];
        }

        return new Content($headers, new DateTime('@' . filemtime($content_file_path)), $body);
    }

    /**
     * 
     * Detect content delimiter string
     *
     * @param string $content content
     * @return string|null delimiter string(null if content is only headers)
     */
    public static function detectDelimiterString(string $content): ?string {
        $re_end_of_doc_marker = "[\r\n]+\.\.\.[\r\n]+";
        $re_directive_separator = "[\r\n]+---[\r\n]+";

        mb_ereg_search_init($content);
        $end_of_doc_marker_info = mb_ereg_search_pos($re_end_of_doc_marker);
        mb_ereg_search_setpos(0);
        $directive_separator_info = mb_ereg_search_pos($re_directive_separator);
        if ($end_of_doc_marker_info === false && $directive_separator_info === false) {
            return null;
        }
        if (is_array($end_of_doc_marker_info) !== false && $directive_separator_info === false) {
            return $re_end_of_doc_marker;
        }
        if ($end_of_doc_marker_info === false && is_array($directive_separator_info)) {
            return $re_directive_separator;
        }
        return $end_of_doc_marker_info[0] < $directive_separator_info[0]
                ? $re_end_of_doc_marker
                : $re_directive_separator;
    }

    private $headers;
    private $last_update_time;
    private $body;

    private $translatedBody;

    /**
     * Constructor
     *
     * @param array $headers content header map
     * @param DateTime $last_update_time last update time
     * @param string|null $body content body
     */
    private function __construct(array $headers, DateTime $last_update_time, $body) {
        assert(0 < ($headers));

        $this->headers = $headers;
        $this->last_update_time = $last_update_time;
        $this->body = $body;

        $this->translatedBody = [];
    }

    /**
     * Can list up?
     * 
     * @return bool
     */
    public function canListUp(): bool {
        $exclude = $this->getValueOf('ExcludeFromList', false);
        return is_null($exclude) || $exclude === false;
    }

    /**
     * Last update time
     *
     * @return DateTime
     */
    public function getLastUpdateTime(): DateTime {
        return $this->last_update_time;
    }

    /**
     * Date and time
     * 
     * @return DateTime
     */
    public function getDateAndTime(): DateTime {
        $dt = $this->last_update_time;
        $literal = $this->getValueOf('DateAndTime', false);
        if (is_null($literal)) {
            $literal = $this->getValueOf('Date', false);
            if (is_null($literal) === false) {
                $dt = DateTime::createFromFormat('Y-m-d', $literal);
                $dt->setTime(0, 0, 0);
            }
        }
        else {
            $dt = DateTime::createFromFormat(strpos($literal, '+') !== false ? DateTime::ATOM : 'Y-m-d\TH:i:s',
                                            $literal);
        }
        return $dt;
    }

    /**
     * Has target?
     * 
     * @return bool
     */
    public function hasTarget(): bool {
        return $this->hasHeader('Target');
    }
  
    /**
     * Target
     * 
     * @return string
     */
    public function getTarget(): string {
        assert($this-hasTarget());
        return $this->getValueOf('Target', true);
    }

    /**
     * Has target text?
     * 
     * @return bool
     */
    public function hasTargetText(): bool {
        return $this->hasHeader('TargetText');
    }
  
    /**
     * Target
     * 
     * @return string
     */
    public function getTargetText(): string {
        assert($this-hasTargetText());

        return $this->getValueOf('TargetText', true);
    }

    /**
     * Has support target?
     * 
     * @return bool
     */
    public function hasSupportTarget(): bool {
        return $this->hasHeader('SupportTarget');
    }
  
    /**
     * Support target
     * 
     * @return string
     */
    public function getSupportTarget(): string {
        assert($this-hasSupportTarget());
        return $this->getValueOf('SupportTarget', true);
    }

    /**
     * Has template?
     * 
     * @return bool
     */
    public function hasTemplate(): bool {
        return $this->hasHeader('Template');
    }
  
    /**
     * Template
     * 
     * @return string
     */
    public function getTemplate(): string {
        assert($this-hasTemplate());
        return $this->getValueOf('Template', true);
    }

    /**
     * Has title?
     * 
     * @return bool
     */
    public function hasTitle(): bool {
        return $this->hasHeader('Title');
    }

    /**
     * Title
     * 
     * @param string|null $lang language
     * @return string
     */
    public function getTitle(string $lang = null): string {
        assert($this->hasTitle());
        return $this->getTargetValueFrom($this->getValueOf('Title', true), $lang);
    }

    /**
     * Has author?
     * 
     * @return bool
     */
    public function hasAuthor(): bool {
        return $this->hasHeader('Author');
    }

    /**
     * Author
     * 
     * @param string|null $lang language
     * @return string
     */
    public function getAuthor(string $lang = null): string {
        assert($this->hasAuthor());
        return $this->getTargetValueFrom($this->getValueOf('Author', true), $lang);
    }

    /**
     * Has category?
     * 
     * @return bool
     */
    public function hasCategory(): bool {
        return $this->hasHeader('Category');
    }

    /**
     * Category(to use Atom Syndication Format)
     * Value scheme: http://xmlns.com/wordnet/1.6/(see http://xmlns.com/2001/08/wordnet/)
     * 
     * @return string|null
     */
    public function getCategory(): ?string {
        assert($this->hasCategory());
        return $this->getTargetValueFrom($this->getValueOf('Category', true), null);
    }

    /**
     * Has description?
     * 
     * @return bool
     */
    public function hasDescription(): bool {
        return $this->hasHeader('Description');
    }

    /**
     * Description
     * 
     * @param string|null $lang language
     * @return string
     */
    public function getDescription(string $lang = null): string {
        assert($this->hasDescription());
        return $this->getTargetValueFrom($this->getValueOf('Description', true), $lang);
    }

    /**
     * Get tags
     * 
     * @return aray
     */
    public function getTags(): array {
        return $this->headers['Tags'];
    }

    /**
     * Has tag?
     * 
     * @return aray
     */
    public function hasTag(string $tag): bool {
        assert($tag !== '');
        return in_array($tag, $this->getTags());
    }

    /**
     * Get specified header value
     * 
     * @param string $header_name language
     * @param string|null $lang language
     * @return string|null header value
     */
    public function getHeaderValueOf(string $header_name, string $lang = null): ?string {
        return $this->getTargetValueFrom($this->getValueOf($header_name, false), $lang);
    }

    /**
     * Get representation image path
     * 
     * @return string|null
     */
    public function getRepresentationImageSource(): ?string {
        if ($this->hasHeader('RepresentationImage')) {
            return $this->getValueOf('RepresentationImage', true);
        }

        $xml_string = 0 < count($this->translatedBody)
                    ? current($this->translatedBody)
                    : $this->getTranslatedBody(null);
        $images = [];
        mb_ereg_search_init($xml_string, "<img .*?src=['\"](.+?)['\"]");
        while (mb_ereg_search()) {
            $matches = mb_ereg_search_getregs();
            if ($matches[0] !== '') {
                $images[] = $matches[1];
            }
        }
        return 0 < count($images) ? $images[0] : null;
    }

    /**
     * Get structured data information
     * 
     * @return array
     */
    public function getStructuredDataInfo(): array {
        return array_key_exists('StructuredData', $this->headers) ? $this->headers['StructuredData'] : [];
    }

    /**
     * Content raw body
     * 
     * @param string|null $lang language
     * @return string
     */
    public function getRawBody(string $lang = null): string {
        $numeric_char_references = [
            '&#x2d;' => '-',
            '&#x2e;' => '.'
        ];
        $body = $this->getTargetValueFrom($this->body, $lang);
        foreach ($numeric_char_references as $key => $value) {
            $body = mb_ereg_replace($key, $value, $body);
        }
        return $body;
    }

    /**
     * Content translated body
     * 
     * @param string|null $lang language
     * @return string
     */
    public function getTranslatedBody(string $lang = null): string {
        $key = $lang ?? 'generic';
        if (array_key_exists($key, $this->translatedBody) === false) {
            if (is_null(self::$parser) !== false) {
                self::$parser = new Parsedown();
            }
            $this->tranlatedBody[$key] = self::$parser->text($this->getRawBody($lang));
        }
        return $this->tranlatedBody[$key];
    }

    /**
     * Get beginning of content body
     * 
     * @param int $limit_lengh limit length
     * @param string|null $lang language
     * @return PartOfContent
     */
    public function getBeginningOfBody(int $limit_length, string $lang = null): PartOfContent {
        assert(0 < $limit_length);

        $body_text = strip_tags($this->getTranslatedBody($lang));

        $part = new PartOfContent();
        $part->part = '';
        mb_ereg_search_init($body_text, '[^\p{Zl}\p{Zp} \r\n]+*?[\p{Zl}\p{Zp} \r\n]+');
        while (mb_ereg_search()) {
            $matches = mb_ereg_search_getregs();
            $match = mb_ereg_replace('[ \r\n\t]+', ' ', trim($matches[0]));
            if ($match !== '') {
                if ($limit_length < mb_strlen($part->part . $match)) {
                    $part->hasFollowing = true;
                    break;
                }
                if ($part->part !== ''
                && ($part->part[mb_strlen($part->part) - 1] !== ' ' || $match[0] !== ' ')) {
                    $part->part = $part->part . ' ';
                }
                $part->part = $part->part . $match;
            }
        }
        $part->part = mb_ereg_replace(' \r\n\t', ' ', trim($part->part !== '' ? $part->part : $body_text));
        return $part;
    }

    /**
     * Has header?
     * 
     * @param string $header_name header name
     * @return bool
     */
    private function hasHeader(string $header_name): bool {
        return array_key_exists($header_name, $this->headers);
    }

    /**
     * Get header value
     * 
     * @param string $header_name header name
     * @param bool $required required
     * @return mixed value
     */
    private function getValueOf(string $header_name, bool $required) {
        if (array_key_exists($header_name, $this->headers) === false) {
            if ($required !== false) {
                throw new Exception('No header: ' + $header_name);
            }
            return null;
        }

        return $this->headers[$header_name];
    }

    /**
     * Get value
     * 
     * @param mixed $values values
     * @param string|null $lang target language
     * @return mixed
     */
    private function getTargetValueFrom($values, string $lang = null) {
        if (is_string($values) || is_array($values) === false) {
            return $values;
        }

        $count = count($values);
        if ($count === 0) {
            return null;
        }

        if ($count === 1
        || is_null($lang)
        || array_key_exists($lang, $values) === false) {
            return current($values);
        }

        return $values[$lang];
    }
}
