<?php

/**
 * Build URL string from array of data.
 * @param array $parts
 *  Output of parse_url(), however 'query' part can also be array as output of parse_str()
 * @return string
 */
function wtools_build_url(array $parts) {
	if (!empty($parts['query']) && is_array($parts['query'])) {
		$parts['query'] = http_build_query($parts['query']);
	}
	return (isset($parts['scheme']) ? "{$parts['scheme']}:" : '') . 
		((isset($parts['user']) || isset($parts['host'])) ? '//' : '') . 
		(isset($parts['user']) ? "{$parts['user']}" : '') . 
		(isset($parts['pass']) ? ":{$parts['pass']}" : '') . 
		(isset($parts['user']) ? '@' : '') . 
		(isset($parts['host']) ? "{$parts['host']}" : '') . 
		(isset($parts['port']) ? ":{$parts['port']}" : '') . 
		(isset($parts['path']) ? "{$parts['path']}" : '') . 
		(isset($parts['query']) ? "?{$parts['query']}" : '') . 
		(isset($parts['fragment']) ? "#{$parts['fragment']}" : '');
}

/**
 * Similar to parse_url(), it will parse query part as well.
 *
 * @param string|null $url
 *  If NULL, then $_SERVER['REQUEST_URI'] will be used.
 * @return array
 */
function wtools_parse_url($url = NULL) {
	if (!$url) {
		$url = $_SERVER['REQUEST_URI'];
	}
	$parsed_url = parse_url($url);
	if (!empty($parsed_url['query'])) {
		parse_str($parsed_url['query'], $parsed_query);
		$parsed_url['query'] = $parsed_query;
	}
	else {
		$parsed_url['query'] = array();
	}
	return $parsed_url;
}

/**
 * Get base URL path to upload directory in HTTP/HTTPS sensitive way.
 * @return string
 */
function wtools_get_upload_base_url() {
	$upload_dir_info = wp_get_upload_dir(NULL);
	$url = $upload_dir_info['baseurl'];
	if (is_ssl()) {
		$url = preg_replace("/^http:/i", "https:", $url);
	}
	return  $url . '/';
}