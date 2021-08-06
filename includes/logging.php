<?php
namespace Murmurations\Aggregator;

$murmurations_log_buffer = '';

function llog( $content, $meta = null ) {

	global $murmurations_log_buffer;

	$file = Settings::get( 'log_file' );
	$mode = Settings::get( 'logging_mode' );

  if ( $mode === 'none' ){
    return;
  }

	$log  = date( DATE_ATOM ) . ' ';
	$log .= $meta ? $meta . ': ' : '';
	$log .= ( is_array( $content ) || is_object( $content ) ) ? print_r( (array) $content, true ) : $content;

	if ( is_writable( $file ) ) {
		if ( $mode === 'single' ) {
			$flag                     = null;
			$murmurations_log_buffer .= $log . "\n";
			$log                      = $murmurations_log_buffer;
		} else {
			$flag = FILE_APPEND;
		}
		return file_put_contents( $file, $log . "\n", $flag );
	} else {
		Notices::set( 'Log file is not writable: ' . $file, 'notice' );
	}
}

function debug( $content, $meta = null ) {

	$out  = '<pre>';
	$out .= $meta ? $meta . ': ' : '';
	$out .= ( is_array( $content ) || is_object( $content ) ) ? print_r( (array) $content, true ) : $content;
	$out .= '</pre>';

	if ( Settings::get( 'debug_to_log' ) ) {
		llog( $content, $meta );
	}

	return $out;

}

function error( $message, $severity = 'warn' ) {
	llog( $message, 'error' );
	Notices::set( $message, $severity );
	if ( $severity == 'fatal' ) {
		exit( $message );
	}
}


?>
