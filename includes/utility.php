<?php

function wtools_array_walk_recursive($array, $callback, $parent_keys = array(), &$output, $args = array()) {
	foreach ($array as $key => $value) {
		if (is_array($value)) {
			wtools_array_walk_recursive($value, $callback, array_merge($parent_keys, array($key)), $output, $args);
		}
		else {
			$callback(array_merge($parent_keys, array($key)), $value, $output, $args);
		}
	}
}