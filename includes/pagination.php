<?php

/**
 * Get first page URL for paginated page.
 *
 * @param string|null $url
 *  If NULL, then $_SERVER['REQUEST_URI'] will be used.
 * @return type
 */
function wtools_get_first_page($url = NULL) {
	wtools_include('wtools', 'url');
	$url = wtools_parse_url($url);
	$url['query']['paged'] = 1;
	return wtools_build_url($url);
}

/**
 * Get last page URL for a paginated page.
 *
 * @param int $total_pages
 * @param string|null $url
 *  If NULL, then $_SERVER['REQUEST_URI'] will be used.
 * @return string
 */
function wtools_get_last_page($total_pages, $url = NULL) {
	wtools_include('wtools', 'url');
	$url = wtools_parse_url($url);
	$url['query']['paged'] = $total_pages;
	return wtools_build_url($url);
}

/**
 * Get next page URL for a paginated page.
 *
 * @param int $total_pages
 * @param string|null $url
 *  If NULL, then $_SERVER['REQUEST_URI'] will be used.
 * @return string
 */
function wtools_get_next_page($total_pages, $url = NULL) {
	wtools_include('wtools', 'url');
	$url = wtools_parse_url($url);
	$current_page = !empty($url['query']['paged']) ? (int) $url['query']['paged'] : 1;
	if ($current_page >= $total_pages) {
		// No next page.
		return NULL;
	}
	$url['query']['paged'] = $current_page + 1;
	return wtools_build_url($url);
}

/**
 * Get previous page URL for a paginated URL.
 *
 * @param string|null $url
 *  If NULL, then $_SERVER['REQUEST_URI'] will be used.
 * @return string
 */
function wtools_get_previous_page($url = NULL) {
	wtools_include('wtools', 'url');
	$url = wtools_parse_url($url);
	$current_page = !empty($url['query']['paged']) ? (int) $url['query']['paged'] : 1;
	if ($current_page <= 1) {
		// No next page.
		return NULL;
	}
	$url['query']['paged'] = $current_page - 1;
	return wtools_build_url($url);
}

/**
 * Generate pagination links for a route
 *
 * @param string $route
 * @param array $query
 * @param int $total_count
 * @param int $items_per_page
 * @param string $page_number_parameter
 * @return array
 */
function wtools_paginate_route($route, $query, $total_count, $items_per_page = 10, $page_number_parameter = 'page') {
	$number_of_pages = ceil($total_count / (float) $items_per_page);
	$pages = array();
	for ($page_number = 0; $page_number < $number_of_pages; $page_number++) {
		$page_query = $query;
		$page_query[$page_number_parameter] = $page_number;
		$pages[] = wtools_get_url($route, $page_query);
	}
	return $pages;
}

/**
 * Generate pagination links for an admin url
 *
 * @param string $admin_path
 * @param array $query
 * @param int $total_count
 * @param int $items_per_page
 * @param string $page_number_parameter
 * @return array
 */
function wtools_paginate_admin_url($admin_path, $query, $total_count, $items_per_page = 10, $page_number_parameter = 'page') {
	$number_of_pages = ceil($total_count / (float) $items_per_page);
	$pages = array();
	for ($page_number = 0; $page_number < $number_of_pages; $page_number++) {
		$page_query = $query;
		$page_query[$page_number_parameter] = $page_number;
		if (count($page_query)) {
			$query_string = http_build_query($page_query);
		}
		else {
			$query_string = '';
		}
		$pages[] = admin_url("$admin_path?{$query_string}");
//		$pages[] = wtools_get_url($route, $page_query);
	}
	return $pages;
}
