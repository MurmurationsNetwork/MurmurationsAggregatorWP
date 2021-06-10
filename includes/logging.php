<?php
namespace Murmurations\Aggregator;

function log( $content , $meta = null ) {
  $log = date( DATE_ATOM ) . " ";
  $log .= $meta ? $meta . ': ' : '';
  $log .=  ( is_array( $content ) || is_object( $content ) ) ? print_r( (array) $content, true ) : $content;
  if ( is_writable( self::$log_file ) ) {
    if ( self::$no_log_append ){
      $flag = null;
      self::$log_buffer .= $log . "\n";
      $log = self::$log_buffer;
    } else {
      $flag = FILE_APPEND;
    }
    return file_put_contents( self::$log_file, $log . "\n", $flag );
  } else {
    return "Log file is not writable: " . self::$log_file;
  }
}


?>
