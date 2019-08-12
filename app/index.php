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
    public function render(): string {
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

        $uri_server_vars = $this->server_vars['UNENCODED_URL'] !== false
                        ? 'UNENCODED_URL'
                        : 'REQUEST_URI';
        $request_path = rawurldecode($this->server_vars[$uri_server_vars]);

        if ($this->server_vars['PHP_SELF'] !== false) {
            $script_path = $this->server_vars['PHP_SELF'];
            $script_path = mb_substr($script_path, 0, mb_strpos($script_path, '/index.php'));

            if (mb_strpos($request_path, $script_path) === 0) {
                $request_path = mb_substr($request_path, mb_strlen($script_path));
            }
        }
        $content_path = urldecode(explode('?', $request_path)[0]);

        $provider = new ContentsProvider(__DIR__ . '/contents', new DateTime());
        $controller = new Controller($provider, $this->config, $env);
        return $controller->render($content_path !== '' && $content_path !== '/' ? $content_path : '/index');
    }

    /**
     * Get language
     */
    private function getLang(): ?string {
        if ($this->server_vars['QUERY_STRING'] !== false) {
            $params = [];
            parse_str($this->server_vars['QUERY_STRING'], $params);
            if (array_key_exists('lang', $params) !== false) {
                return $params['lang'];
            }
        }

        if (array_key_exists('lang', $this->config) !== false) {
            return $this->config['lang'];
        }

        if ($this->server_vars['HTTP_ACCEPT_LANGUAGE'] !== false) {
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

function getServerVars(): array {
    $server_vars = [
        'REMOTE_ADDR' => filter_input(INPUT_SERVER, 'REMOTE_ADDR'),
        'REQUEST_URI' => filter_input(INPUT_SERVER, 'REQUEST_URI'),
        'UNENCODED_URL' => filter_input(INPUT_SERVER, 'UNENCODED_URL'),
        'PHP_SELF' => filter_input(INPUT_SERVER, 'PHP_SELF'),
        'QUERY_STRING' => filter_input(INPUT_SERVER, 'QUERY_STRING'),
        'HTTP_ACCEPT_LANGUAGE' => filter_input(INPUT_SERVER, 'HTTP_ACCEPT_LANGUAGE')
    ];
    return $server_vars;
}

try {
    $app = new BoothCmsApp(getServerVars(), $config);
    echo $app->render();
}
catch (Exception $e) {
    header(filter_input(INPUT_SERVER, 'SERVER_PROTOCOL') . ' 500 Internal Server Error');
    echo 'Exception occured: ',  $e->getMessage(), "\n";
}
