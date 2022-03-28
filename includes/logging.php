<?php
/**
 * Logging and error handling
 *
 * @package Murmurations Aggregator
 */

namespace Murmurations\Aggregator;

$murmurations_log_buffer = '';

/**
 * Write to the log
 *
 * @param  mixed  $content Things to be logged.
 * @param  string $meta Optional description of things.
 */
function llog( $content, $meta = null ) {

	global $murmurations_log_buffer;

	$file = Settings::get( 'log_file' );
	$mode = Settings::get( 'logging_mode' );

	if ( 'none' === $mode ) {
		return;
	}
	// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
	$log  = date( DATE_ATOM ) . ' ';
	$log .= $meta ? $meta . ': ' : '';
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- ignore compaints about print_r in production (duh)
	$log .= ( is_array( $content ) || is_object( $content ) ) ? print_r( (array) $content, true ) : $content;

	if ( is_writable( $file ) ) {
		if ( 'single' === $mode ) {
			$flag                     = null;
			$murmurations_log_buffer .= $log . "\n";
			$log                      = $murmurations_log_buffer;
		} else {
			$flag = FILE_APPEND;
		}
		return file_put_contents( $file, $log . "\n", $flag ); // phpcs:ignore
	} else {
		Notices::set( 'Log file is not writable: ' . $file, 'notice' );
	}
}

/**
 * Output debugging information
 *
 * @param  mixed  $content Things to be debugged.
 * @param  string $meta Optional description.
 * @return string the HTML formatted debugging output.
 */
function debug( $content, $meta = null ) {

	$out  = '<pre>';
	$out .= $meta ? $meta . ': ' : '';
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- ignore compaints about print_r in production (duh)
	$out .= ( is_array( $content ) || is_object( $content ) ) ? print_r( (array) $content, true ) : $content;
	$out .= '</pre>';

	if ( Settings::get( 'debug_to_log' ) ) {
		llog( $content, $meta );
	}

	return $out;

}

/**
 * Basic error handling
 *
 * @param  string $message A description of what went wrong.
 * @param  string $severity Severity of the error.
 */
function error( $message, $severity = 'warn' ) {
	llog( $message, 'error' );
	Notices::set( $message, $severity );
	if ( 'fatal' === $severity ) {
		Utils::e( $message );
		exit();
	}
}
