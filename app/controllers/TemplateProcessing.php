<?php
/**
 * Twig template processing classes and traits
 *
 * @license https://opensource.org/licenses/mit-license.html MIT License
 */

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Template processing trait
 */
trait TemplateProcessing
{
    private static $COMMON_PATH = 'views/common';

    /**
     * Get template path
     * 
     * @param string $template_name template name
     * @param string $root_path root path
     * @param string $theme_path theme path
     * @return string template path
     */
    private static function getTemplatePath(string $template_name, string $root_path, string $theme_path): string {
        $template_path = '';
        $template_path_list = [ $theme_path, self::$COMMON_PATH, 'views' ];
        foreach ($template_path_list as $path) {
            $check_path = $root_path . '/' . $path;
            if (file_exists($check_path . '/' . $template_name) !== false) {
                $template_path = $check_path;
                break;
            }
        }
        if ($template_path === '') {
            throw new Exception('Template is not exist: ' . $template_name);
        }
        return $template_path;
    }

    /**
     * Create Twig loader
     * 
     * @param string $template_name template name
     * @param string $root_path root path
     * @param string $theme_path theme path
     * @return Twig_Loader_FileSystem Twig loader
     */
    private static function createTwigLoader(string $template_name, string $root_path, string $theme_path): Twig_Loader_FileSystem {
        assert(0 < count($template_name));
        assert(0 < count($root_path));
        assert(0 < count($theme_path));

        $template_path = self::getTemplatePath($template_name, $root_path, $theme_path);
        $target_path_list = [
            $template_path
        ];
        $parts_path_list = [ $template_path, $root_path . '/' . self::$COMMON_PATH ];
        foreach ($parts_path_list as $parts_path) {
            $check_path = $parts_path. '/twig-parts';
            if (file_exists($check_path) !== false) {
                $target_path_list[] = $check_path;
            }
        }
        return new Twig_Loader_Filesystem($target_path_list);
    }

    /**
     * Create Twig options
     * 
     * @param array $config configuration
     * @param array $env environment
     * @return array twig options
     */
    private static function createTwigOptions(array $config, array $env): array {
        assert(0 < count($env));
        assert(0 < count($config));

        return [
            'autoescape' => false,
            'cache' => $config['twig_enable_cache'] ? $env['root_path'] . '/cache/twig' : false,
            'debug' => $config['twig_enable_debug']
        ];
    }

    /**
     * Create twig instance
     * 
     * @param Twig_Loader_FileSystem $loader Twig loader
     * @param array $options Twig options
     * @return Twig_Environment Twig instance
     */
    private static function createTwig(Twig_Loader_FileSystem $loader, array $options): Twig_Environment {
        $twig = new Twig_Environment($loader, $options);
        if (isset($options['debug']) !== false && $options['debug'] !== false) {
            $twig->addExtension(new Twig_Extension_Debug());
        }
        return $twig;
    }

    /**
     * Escape mustaches string
     * 
     * @param string $value value to escape
     * @return string escaped string
     */
    private static function escapeMustaches(string $value): string {
        return mb_ereg_replace('{{', '{&#8203;{', mb_ereg_replace('{{{', '{&#8203;{&#8203;{', $value));
    }
}

final class StructuredDataRenderer
{
    use TemplateProcessing;

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
    private $template_vars;

    public function __construct(array $config, array $env, array $template_vars) {
        $this->config = $config;
        $this->env = $env;

        $this->template_vars = $template_vars;
    }

    /**
     * render structured data JSON-LD
     * 
     * @param array $vars specified variables
     * @return string rendered JSON-LD
     */
    public function render(array $vars): string {
        $template_name = 'StructuredData-' . $vars['type'] . '.json';
        $loader = $this->createTwigLoader($template_name, $this->env['root_path'], $this->template_vars['theme_path']);
        $options = $this->createTwigOptions($this->config, $this->env);
        $twig = $this->createTwig($loader, $options);
        $merged_vars = array_merge($this->config, $this->template_vars, $vars);
        if (array_key_exists('site_author', $merged_vars) === false
        && array_key_exists('author', $merged_vars) !== false) {
            $merged_vars['site_author'] = $merged_vars['author'];
        } elseif (array_key_exists('site_author', $merged_vars) !== false
        && array_key_exists('author', $merged_vars) === false) {
            $merged_vars['author'] = $merged_vars['site_author'];
        }
        return $twig->render($template_name, $merged_vars);
    }
}
