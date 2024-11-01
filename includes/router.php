<?php

/**
 * Class helping to create custom dynamic pages.
 * Implements singleton pattern.
 */
class WTools_Router {

	private $routes = array();

	/**
	 * To be called in 'init' or before.
	 *
	 * @return WTools_Router
	 */
	public static function getInstance() {
		static $router = null;
		if (!$router) {
			$router = new WTools_Router();
		}
		return $router;
	}

	private function __construct() {
		add_action('parse_request', array($this, 'wp_parse_request'));
	}

	public function wp_parse_request(&$wp) {
		if (empty($wp->request)) {
			// Early exit. We do not handle home page noway.
			return;
		}
		foreach ($this->routes as $path => $route_info) {
			if (preg_match("@^$path$@", $wp->request)) {
				if (isset($route_info['file'])) {
					require_once($route_info['file']);
				}
				if (isset($route_info['access_callback'])) {
					if (!call_user_func($route_info['access_callback'])) {
						wp_die( __( 'Sorry, you are not allowed to access this page.' ), 403 );
					}
				}
				if (isset($route_info['page_callback'])) {
					call_user_func($route_info['page_callback']);
					exit;
				}
			}
		}
	}

	/**
	 * Add route.
	 *
	 * @param string $path
	 * @param array $route_info
	 */
	public function addRoute($path, $route_info) {
		$this->routes[$path] = $route_info;
	}
}

/**
 * Helper function to get URL associated with WTools routes.
 *
 * @param array $route
 * @param array $query
 * @return string
 */
function wtools_get_url($route, $query = array()) {
	if (count($query)) {
		$query_string = http_build_query($query);
	}
	else {
		$query_string = '';
	}
	return site_url("index.php/{$route}?{$query_string}");
}
