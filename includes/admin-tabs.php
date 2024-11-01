<?php

/**
 * Display tabs in a admin page.
 *
 * @param array $tabs
 * @return string
 */
function wtools_admin_tabs($tabs) {
	$output = '<h2 class="nav-tab-wrapper wp-clearfix">';
	$current_slug = $_GET['page'];
	foreach ($tabs as $slug => $info) {
		$query = isset($info['query']) ? $info['query']: array();
		$query['page'] = $slug;
		if ($slug == $current_slug) {
			$active_class = 'nav-tab-active';
		}
		else {
			$active_class = '';
		}
		$output .= '<a href="' . admin_url( 'admin.php?' . http_build_query($query) ) . '" class="nav-tab ' . $active_class . '">' . $info['label'] . '</a>';
	}
	$output .= '</h2>';
	return $output;
}
