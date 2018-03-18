<?php
use \Michelf\MarkdownExtra;

class BoothCmsApp
{
	private $plugins;

	public function __construct()
	{
	}

	public function init()
	{
		$this->load_plugins();
		$this->load_config();
	}

	public function render()
	{
		// TODO 実装
		// Get request url and script url
		$request_url = urldecode($_SERVER['REQUEST_URI']);
		$script_url  $_SERVER['PHP_SELF'];

		// Get our url path and trim the / of the left and the right
		$url = '';
		if ($request_url != $script_url) {
			$script_path = substr($script_url, 0, strlen($script_url) - strlen('index.php'));
			$url = str_replace('/', '\/', $script_path);
			$url = preg_replace('/' . $url . '/', '', $request_url, 1);
			$url = trim($url, '/');
		}
		 // Strip query string
		$url = preg_replace('/\?.*/', '', $url);
		$this->run_hooks('request_url', array(&$url));
	}

	private function load_plugins()
	{
		$this->plugins = array();

		$entries = scandir(PLUGINS_DIR);
		foreach ($entries as $entry) {
			$entry_path = $path . "/" . $entry;
			if (is_dir($entry_path) === FALSE
			&& strpos($entry, ".php") === (strlen($entry) - 4)) {
				include_once($entry_path);
				$plugin_name = substr($entry, 0, (strlen($entry) - 4));
					$obj = ;
					$this->plugins[] = new $plugin_name;
				}
			}
		}
		$this->call_plugin_api('plugins_loaded');
	}

	private function call_plugin_api($api_name, $args = array())
	{
		if (empty($this->plugins)) {
			return;
		}

		$param = array(NULL, $api_name);
		foreach ($this->plugins as $plugin) {
			$param[0] = $plugin;
			if (is_callable($param)) {
				call_user_func_array($param, $args);
			}
		}
	}

	private function load_config()
	{
		global $config;
		@include_once(ROOT_DIR ."config.php");

		$defaults = array(
			'site_title' => 'Booth CMS',
			'base_url' => $this->base_url(),
			'theme' => 'default',
			'date_format' => 'jS M Y',
			'twig_config' => array('cache' => false, 'autoescape' => false, 'debug' => false),
			'pages_order_by' => 'alpha',
			'pages_order' => 'asc',
			'excerpt_length' => 50
		);

		if (is_array($config)) {
			$config = array_merge($defaults, $config);
		}
		else {
			$config = $defaults;
		}

		$this->call_plugin_api('config_loaded', array(&$config));
	}
}
