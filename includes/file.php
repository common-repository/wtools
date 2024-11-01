<?php

define('WTOOLS_FILE_EXISTS_REPLACE', 0);
define('WTOOLS_FILE_EXISTS_RENAME', 1);
define('WTOOLS_FILE_EXISTS_ERROR', 2);

/**
 * Check if uploaded file exist corresponding to a form field.
 *
 * @param string $field_name
 * @return boolean
 */
function wtools_file_upload_exists($field_name) {
	if (is_array($_FILES[$field_name]['name'])) {
		foreach ($_FILES[$field_name]['error'] as $error) {
			if ($error != 0) {
				return false;
			}
		}
		return TRUE;
	}
	else {
		return isset($_FILES[$field_name]['error']) && $_FILES[$field_name]['error'] == 0;
	}
}

/**
 * Find next that can be used to when a file with same name already exists.
 *
 * @param string $path
 * @return boolean
 */
function wtools_file_find_next_name($path) {
	$new_file_name = $path;
	$original_fileinfo = pathinfo($path);
	$append = 0;
	while(file_exists($new_file_name)) {
		$new_fileinfo = $original_fileinfo;
		$new_fileinfo['filename'] .= "_$append";
		$ds = DIRECTORY_SEPARATOR;
		$new_file_name = "{$new_fileinfo['dirname']}{$ds}{$new_fileinfo['filename']}.{$new_fileinfo['extension']}";
		$append++;
	}
	return $new_file_name;
}

/**
 * 
 * @param string $field_name
 * @param string $destination
 * @param int $replace
 *  Any of WTOOLS_FILE_EXISTS_REPLACE, WTOOLS_FILE_EXISTS_RENAME and WTOOLS_FILE_EXISTS_ERROR
 * @param array $error_messages
 *  It will be filled with error messages, if any.
 * @return boolean
 */
function wtools_file_upload($field_name, $destination, $replace = WTOOLS_FILE_EXISTS_RENAME, &$error_messages) {
	$error = false;
	$error_messages = array();
	
	if(!isset($_FILES[$field_name])) {
		$error_messages[] = _('No upload exists for the given field');
		$error = true;
	}
	$uploaded_files_info = array();
	if (is_array($_FILES[$field_name]['name'])) {
		foreach ($_FILES[$field_name]['name'] as $index => $name) {
			$uploaded_files_info[] = array(
				'name' => $name,
				'error' => $_FILES[$field_name]['error'][$index],
				'size' => $_FILES[$field_name]['size'][$index],
				'tmp_name' => $_FILES[$field_name]['tmp_name'][$index],
				'type' => $_FILES[$field_name]['type'][$index],
			);
		}
	}
	else {
		$uploaded_files_info[] = array(
			'name' => $_FILES[$field_name]['name'],
			'error' => $_FILES[$field_name]['error'],
			'size' => $_FILES[$field_name]['size'],
			'tmp_name' => $_FILES[$field_name]['tmp_name'],
			'type' => $_FILES[$field_name]['type'],
		);
	}

	$target_files = array();
	foreach ($uploaded_files_info as $file_info) {
		$target_file = $destination . DIRECTORY_SEPARATOR . basename($file_info["name"]);
		if (file_exists($target_file)) {
			if ($replace == WTOOLS_FILE_EXISTS_RENAME) {
				$target_file = wtools_file_find_next_name($target_file);
			}
			elseif ($replace == WTOOLS_FILE_EXISTS_REPLACE) {
				if (!unlink($target_file)) {
					$error_messages[$file_info["name"]] = _('Old file could not be deleted for replacing');
					$error = true;
				}
			}
			elseif ($replace == WTOOLS_FILE_EXISTS_ERROR) {
				$error_messages[$file_info["name"]] = _('File exists already');
				$error = true;
			}
		}

		if (!file_exists($destination)) {
			if (!mkdir($destination, 0777, true)) {
				$error_messages[] = _('Destination directory could not be created');
				$error = true;
			}
		}
		if (!$error) {
			if (!move_uploaded_file($file_info["tmp_name"], $target_file)) {
				$error_messages[$file_info["name"]] = _('Upload file could not be moved');
				$error = true; 
			}
		}
		$target_files[] = $target_file;
	}
	

	if (!$error) {
		return $target_files;
	}
	else {
		return FALSE;
	}
}

/**
 * Remove the directory path from given file path.
 *
 * @param string $dir
 * @param string $file_path
 *  Should be within $dir
 * @return string
 */
function wtools_file_strip_dir($dir, $file_path) {
	// + 1 is to remove slash.
	return substr($file_path, strlen($dir) + 1);
}

/**
 * Remove upload directory path part from an absolute file path.
 *
 * @param string $file_path
 * @return string
 */
function wtools_file_strip_upload_dir($file_path) {
	$result = null;
	$upload_dir_info = wp_get_upload_dir(NULL);
	$upload_base_dir = $upload_dir_info['basedir'];
	if (is_array($file_path)) {
		$result = array();
		foreach ($file_path as $index => $path) {
			$result[$index] = wtools_file_strip_dir($upload_base_dir, $path);
		}
	}
	else {
		$result = wtools_file_strip_dir($upload_base_dir, $file_path);
	}
	return $result;
}

/**
 * Check if given directory is empty or not.
 *
 * @param string $path
 * @return boolean
 */
function wtools_directory_empty($path) {
	$count = 0;
	if (file_exists($path) && is_dir($path)) {
		$count = count(scandir($path)) == 2;
	}
	return $count;
}
