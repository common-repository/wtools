<?php

interface WTools_Form {
	public function form($args);
	public function validate();
	public function submit();
	public function getFormId();
	public function getDelta();
	public function declareFields();
}

/**
 * Base class that all application forms need to extend.
 */
abstract class WTools_Form_Base implements WTools_Form {
	private $form_id;
	public $errors = array();
	public $data = array();
	private $delta;
	public $rebuild = false;
	public $values = array();
	public $args;

	/**
	 *
	 * @param string $delta
	 *  A unique value that can be used to distinguish when same form is used multiple times in a page.
	 * @param array $args
	 */
	public function __construct($delta, $args = array()) {
	  $this->form_id = get_class($this);
	  $this->delta = $delta;
	  $this->args = $args;
	}

	/**
	 * Get HTML attributes for the form element.
	 * @return array
	 */
	public function formAttributes() {
		return array();
	}

	/**
	 * Get form id (class name).
	 *
	 * @return string
	 */
	public function getFormId() {
		return $this->form_id;
	}
	
	public function getDelta() {
		return $this->delta;
	}

	/**
	 * Mark the field has an error along with message that need to pass to user.
	 *
	 * @param string|array $field
	 * @param string $message
	 */
	protected function setError($field, $message) {
		if (is_array($field)) {
			$field = static::getFieldNameFromArray($field);
		}
		$this->errors[$field] = $message;
	}

	/**
	 * Get error message associated with the field.
	 *
	 * @param string $field
	 * @return string
	 */
	public function printError($field) {
		if (is_array($field)) {
			$field = static::getFieldNameFromArray($field);
		}
		return !empty($this->errors[$field])
			? '<span class="form-error">' . $this->errors[$field] . '</span>'
			: '';
	}
	
	/**
	 * Copied from drupal_array_get_nested_value().
	 *
	 * @param array $array
	 * @param array $parents
	 * @param type $key_exists
	 * @return type
	 */
	public static function arrayGetNestedValue(array &$array, array $parents) {
		$ref = &$array;
		foreach ($parents as $parent) {
		  if (is_array($ref) && array_key_exists($parent, $ref)) {
			$ref = &$ref[$parent];
		  }
		  else {
			$null = NULL;
			return $null;
		  }
		}
		return $ref;
	}

	/**
	 * Build form field name from array.
	 *
	 * @param array $field_array
	 * @return string
	 */
	public static function getFieldNameFromArray($field_array) {
		$first_part = array_shift($field_array);
		$field = $first_part;
		if (count($field_array)) {
			$field .= '[' . implode('][', $field_array) . ']';
		}
		return $field;
	}

	/**
	 * Get value associated with a form field.
	 *
	 * @param string $field
	 * @return string
	 */
	public function getValue($field) {
		if (is_array($field)) {
			$field_submitted = $field;
			$field = static::getFieldNameFromArray($field);
		}
		else {
			$field_submitted = array($field);
		}
		$value = static::arrayGetNestedValue($this->values, $field_submitted);
		if (!isset($value)) {
			if (isset($this->data[$field])) {
				$value = $this->data[$field];
			}
			else {
				$value = '';
			}
		}
		return $value;
	}

	/**
	 * Set value for a form field.
	 *
	 * @param string|array $field
	 * @param mized $value
	 */
	public function setValue($field, $value) {
		if (is_array($field)) {
			$field = static::getFieldNameFromArray($field);
		}
		if (isset($this->values[$field])) {
			$value =  $this->values[$field];
		}
		$this->data[$field] = $value;
	}

	/**
	 * Get HTML escaped field value. Useful for textarea.
	 * @param string|array $field
	 * @return string
	 */
	public function getValueHtmlEscaped($field) {
		return esc_html($this->getValue($field));
	}

	/**
	 * Render given template.
	 *
	 * @param string $tempalate_path
	 * @return string
	 */
	protected function renderTemplate($tempalate_path) {
		ob_start();
		include $tempalate_path;
		$string = ob_get_clean();
		return $string;
	}

	/**
	 * Utility method helping to mark multiple form field as require (non-empty).
	 *
	 * @param array $field_infos
	 */
	public function validateRequired($field_infos) {
		foreach ($field_infos as $field_info) {
			list ($field, $label) = $field_info;
			if (!is_array($field)) {
				$field = array($field);
			}
			$value = static::arrayGetNestedValue($this->values, $field);
			if (empty($value) && !is_numeric($value)) {
				$this->setError($field, sprintf(_("'%s' is required."), $label));
			}
		}
	}
}

/**
 * Class to handle form classes and objects.
 */
class WTools_Form_Handler {
	public static $forms = array();
	/**
	 * 
	 * @param string $source
	 * @param string $form_id
	 * @param string $delta
	 * @return WTools_Form_Base
	 */
	public static function load($source, $form_id, $delta, $options = array()) {
		wtools_load_form_file($source, $form_id);
		if (isset(static::$forms[$form_id][$delta])) {
			// Currently submitted form, get from static cache.
			$form = WTools_Form_Handler::$forms[$form_id][$delta];
			if ($form->rebuild) {
				// Throw all submitted values.
				$form->values = array();
			}
		}
		else {
			$form = new $form_id($delta);
		}
		if (!empty($options['action'])) {
			$form->action = $options['action'];
		}
		if (!isset($form->method)) {
			$form->method = 'post';
		}
		$form->source = $source;
		return $form;
	}
	/**
	 * 
	 * @param WTools_Form $form
	 * @return string
	 */
	public static function get(WTools_Form_Base $form, $args = array()) {
		if (isset($form->file_upload) && $form->file_upload) {
			$enctype = 'enctype="multipart/form-data"';
		}
		else {
			$enctype = '';
		}

		// Convert attributes array to string.
		$form_attributes = $form->formAttributes();
		$form_attributes_joined = array();
		foreach ($form_attributes as $name => $value) {
			$form_attributes_joined[] = $name . '="' . htmlentities($value) . '"';
		}

		$output = '<form action="" method="' . $form->method . '" ' . $enctype. ' ' . implode(' ', $form_attributes_joined) . '>';
		if (strtolower($form->method) == 'get') {
			foreach ($_GET as $key => $value) {
				$output .= '<input type="hidden" name="' . $key . '" value="' . $value . '">';
			}
		}
		$output .= $form->form($args);
		if (strtolower($form->method) == 'post') {
			$output .= '<input type="hidden" name="form_source" value="' . $form->source . '">';
			$output .= '<input type="hidden" name="form_id" value="' . $form->getFormId() . '|' . $form->getDelta() . '">';
			$output .= '<input type="hidden" name="form_nonce" value="' . wp_create_nonce( $form->getFormId() . '|' . $form->getDelta()) . '">' . '</form>';
		}

		return $output;
	}

	/**
	 * 
	 * @param WTools_Form_Base $form
	 */
	public static function process_submission(WTools_Form_Base $form, $values) {
		wtools_include('wtools', 'form-sanitization');

		$form->values = array();
		wtools_include('wtools', 'utility');
		$values = array_map('stripslashes_deep', $values);

		$field_declarations = array();
		$field_declarations = $form->declareFields();
		$field_declarations = array_merge(
			$field_declarations,
			array(
				'form_id' => 'form_id',
				'form_source' => 'form_source',
				'form_nonce' => 'form_nonce',
			)
		);
		wtools_array_walk_recursive($values, function($keys, $value, &$output, $args) {
			$ref = &$args['field_declarations'];
			foreach ($keys as $name) {
				if (isset($ref[$name])) {
					$ref = &$ref[$name];
				}
				else if (isset($ref['*'])) {
					$ref = &$ref['*'];
				}
				else {
					$ref = NULL;
					break;
				}

			}
			$type = $ref;
			if ($type) {
				$sanitization_function = 'wtools_form_sanitize_field_type_value_' . $type;
				$ref_out = &$output;
				$last_key = array_pop($keys);
				foreach ($keys as $key) {
					if (!isset($ref_out[$key])) {
						$ref_out[$key] = array();
					}
					$ref_out = &$ref_out[$key];
				}
				// Right now we do not check if function exist to five feedback to user.
				$ref_out[$last_key] = $sanitization_function($value);
			}
			
		}, array(), $form->values, array('field_declarations' => $field_declarations));

		
		// Perform validations
		$form->validate();

		// Process submission if no errors.
		if (!count($form->errors)) {
			$form->submit();
		}
	}
}

/**
 * Load form definition from given source (plugin or theme) .
 * 
 * @param string $source
 *  Plugin or theme directory name.
 * @param string $form_id
 *  Form class name.
 */
function wtools_load_form_file($source, $form_id) {
	// Sanitize for security.
	// Generally, remove special characters that can be part for file path.
	$source = sanitize_file_name($source);
	$form_id = sanitize_file_name($form_id);
	$prefix = substr( $source, 0, 2 );
	switch ($prefix) {
		case 't_':
			$base_path = ABSPATH . 'wp-content/themes/';
		case 'p_':
		default:
			$base_path = ABSPATH . 'wp-content/plugins';
	}
	require_once $base_path . "/$source/forms/$form_id.php";
	
	
}

/**
 * Helper function to create form select field.
 *
 * @param string|array $field_name
 * @param array $options
 * @param string $selected_value
 * @return string
 */
function wtools_form_select_field($field_name, $options, $selected_value = NULL, $attributes = array()) {
	if (is_array($field_name)) {
		$field_name = WTools_Form_Base::getFieldNameFromArray($field_name);
	}
	$attributes_tmp = array();
	if (!empty($attributes)) {
		foreach ($attributes as $key => $value) {
			$attributes_tmp[] = $key . '="' . $value . '"';
		}
	}
	$output = '<select name="' . $field_name . '" ' . implode(' ', $attributes_tmp) . '>';
	foreach ($options as $value => $label) {
		$output .= '<option value="' . $value . '" ';
		if (isset($selected_value) && $value == $selected_value ) {
			$output .= 'selected="selected" ';
		}
		$output .= '>' . $label . '</option>';
	}
	$output .= '</select>';
	return $output;
}

/**
 * Helper function to create radio buttons.
 *
 * @param string|array $field_name
 * @param array $options
 * @param string $selected_value
 * @param array $other_options
 * @return string
 */
function wtools_form_radios($field_name, $options, $selected_value = NULL, $other_options = array()) {
	$output = '';
	$item_prefix = isset($other_options['item_prefix']) ? $other_options['item_prefix'] : '';
	$item_suffix = isset($other_options['item_suffix']) ? $other_options['item_suffix'] : '';
	foreach ($options as $value => $label) {
		if ($value == $selected_value) {
			$checked = ' checked="checked" ';
		}
		else {
			$checked = '';
		}
		$output .= $item_prefix . '<label><input type="radio" name="' . $field_name . '" value="' . $value . '" ' . $checked . ' >'. $label . '</label>' . $item_suffix;
	}
	return $output;
}

/**
 * Helper function to create checkboxes.
 *
 * @param string|array $field_name
 * @param boolean $checked
 * @return string
 */
function wtools_form_checkbox($field_name, $checked = FALSE) {
	if (is_array($field_name)) {
		$field_name = WTools_Form_Base::getFieldNameFromArray($field_name);
	}

	if ($checked) {
		$checked_attr = 'checked="checked"';
	}
	else {
		$checked_attr = '';
	}
	$output = '<input type="checkbox" name="'. $field_name . '" ' . $checked_attr . ' value="1">';

	return $output;
}
