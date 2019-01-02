<?php
/**
 * BoothCMS app
 *
 * @copyright D.B.C.
 * @license https://opensource.org/licenses/mit-license.html MIT License
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/ContentsProvider.php';
require_once __DIR__ . '/controllers/Controller.php';

/**
 * BoothCmsApp
 */
final class BoothCmsApp {
    private $server_vars;
    private $config;

    /**
     * Constructor
     *
     * @param array $server_vars server variables
     * @param array $config configuration
     */
    public function __construct(array $server_vars, array $config) {
        $this->server_vars = $server_vars;
        $this->config = $config;
    }

    /**
     * Render
     */
    public function render() {
        $remote_addr = $this->server_vars['REMOTE_ADDR'];
        $env = [
            'root_path' => realpath(__DIR__),
            'lang' => $this->getLang(),
            /*
            'as_develop' => (0 <= mb_strpos($this->config['sitr_url'], 'example.com')
                        || $remote_addr === '::1'
                        || $remote_addr === '127.0.0.1')
             */
            'as_develop' => false
        ];

        $content_path = '';
        if (array_key_exists('REQUEST_URI', $this->server_vars) !== false
        || array_key_exists('UNENCODED_URL', $this->server_vars) !== false) {
            $uri_server_vars = array_key_exists('UNENCODED_URL', $this->server_vars) !== false
                            ? 'UNENCODED_URL'
                            : 'REQUEST_URI';
            $request_path = rawurldecode($this->server_vars[$uri_server_vars]);

            if (array_key_exists('PHP_SELF', $this->server_vars) !== false) {
                $script_path = $this->server_vars['PHP_SELF'];
                $script_path = mb_substr($script_path, 0, mb_strpos($script_path, '/index.php'));

                if (mb_strpos($request_path, $script_path) === 0) {
                    $request_path = mb_substr($request_path, mb_strlen($script_path));
                }
            }
            $content_path = urldecode(explode('?', $request_path)[0]);
        }

        $provider = new ContentsProvider(__DIR__ . '/contents');
        $controller = new Controller($provider, $this->config, $env);
        return $controller->render($content_path !== '' && $content_path !== '/' ? $content_path : '/index');
    }

    /**
     * Get language
     */
    private function getLang() {
        if (array_key_exists('QUERY_STRING', $this->server_vars) !== false) {
            $params = array();
            parse_str($this->server_vars['QUERY_STRING'], $params);
            if (array_key_exists('lang', $params) !== false) {
                return $params['lang'];
            }
        }

        if (array_key_exists('lang', $this->config) !== false) {
            return $this->config['lang'];
        }

        if (array_key_exists('HTTP_ACCEPT_LANGUAGE', $this->server_vars) !== false) {
            // ja,en-US;q=0.7,en;q=0.3
            $lang_list = explode(';', $this->server_vars['HTTP_ACCEPT_LANGUAGE'])[0];
            $langs = array_filter(explode(',', $lang_list), function($lang) {
                return explode('-', $lang)[0];
            });
            return $langs[0];
        }

        return null;
    }
}

try {
    $app = new BoothCmsApp($_SERVER, $config);
    echo $app->render();
}
catch (Exception $e) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
    echo 'Exception occured: ',  $e->getMessage(), "\n";
}
