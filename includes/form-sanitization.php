<?php

function wtools_form_sanitize_field_type_value_form_id($str) {
	return sanitize_text_field($str);
}

function wtools_form_sanitize_field_type_value_form_source($str) {
	return sanitize_text_field($str);
}

function wtools_form_sanitize_field_type_value_form_nonce($str) {
	return sanitize_text_field($str);
}

function wtools_form_sanitize_field_type_value_submit($str) {
	return sanitize_text_field($str);
}

function wtools_form_sanitize_field_type_value_text($str) {
	return sanitize_text_field($str);
}

function wtools_form_sanitize_field_type_value_textarea($str) {
	return sanitize_textarea_field($str);
}

function wtools_form_sanitize_field_type_value_email($str) {
	return sanitize_email($str);
}

function wtools_form_sanitize_field_type_value_int($str) {
	$return = filter_var($str, FILTER_SANITIZE_NUMBER_INT);
	return $return === FALSE ? NULL : (int) $return;
}

function wtools_form_sanitize_field_type_value_float($str) {
	$return = filter_var($str, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
	return $return === FALSE ? NULL : (float) $return;
}
