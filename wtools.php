<?php

/**
 * @package WTools
 */
/*
  Plugin Name: WTools
  Plugin URI: https://wordpress.org/plugins/wtools/
  Description: WTools brings various functions and classes to make developing other plugins faster.
  Version: 1.0
  Author: Junaid P V
  Author URI: https://junix.in/
  License: GPLv2 or later
  Text Domain: wtools
 */

function wtools_include($plugin, $name) {
	require_once WP_PLUGIN_DIR . "/$plugin/includes/{$name}.php";
}

add_action('init', 'wtools_init');

function wtools_init() {
	// Process form submissions, if any.
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['form_id']) && !empty($_POST['form_source']) && !empty($_POST['form_nonce'])) {
		wtools_include('wtools', 'form');
		// All submitted POST values are getting sanitized in WTools_Form_Handler::process_submission()
		// as per field type declarations in form definition.
		// POST values that are do not have their type declared in form definition will not reach
		// validation or submit handlers.
		// Expire for within 12 hour.
		// We believe in Wordpress nonce.
		if (wp_verify_nonce($_POST['form_nonce'], $_POST['form_id']) == 1) {
			list($form_id, $delta) = explode('|', $_POST['form_id']);
			$form = WTools_Form_Handler::load($_POST['form_source'], $form_id, $delta);
			WTools_Form_Handler::process_submission($form, $_POST);
			WTools_Form_Handler::$forms[$form_id][$delta] = $form;
		}
		else {
			// TODO: Let user know.
		}

	}
}
