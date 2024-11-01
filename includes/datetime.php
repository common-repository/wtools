<?php

/**
 * Format given timestamp.
 *
 * @param int $timestamp
 * @param string $format
 *  If not given then options 'date_format' and 'time_format' will be used.
 * @return string
 */
function wtools_format_datetime($timestamp, $format = NULL) {
	if (!$format) {
		$format = get_option('date_format') . ' ' . get_option('time_format');
	}
	// Try to get timezone name
	$timezoneName =  get_option('timezone_string');
	// Otherwise prepare timezone name from offset.
	if (empty($timezoneName)) {
		$gmt_offset = get_option( 'gmt_offset', 0 );
		$timezoneName = timezone_name_from_abbr("", $gmt_offset * 3600, false);
	}
	// Throw exception if timezone could not be determined.
	if (empty($timezoneName)) {
		throw new Exception('Could not determine site timezone.');
	}

	$tz = new DateTimeZone($timezoneName);
	$dt = new DateTime('now', $tz);
	$dt->setTimestamp($timestamp);
	return $dt->format($format);
}
