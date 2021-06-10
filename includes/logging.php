<?php
namespace Murmurations\Aggregator;

$murmurations_log_buffer = '';

function llog( $content , $meta = null ) {

  global $murmurations_log_buffer;

  $file = Config::get('log_file');
  $append = Config::get('log_append');

  $log = date( DATE_ATOM ) . " ";
  $log .= $meta ? $meta . ': ' : '';
  $log .=  ( is_array( $content ) || is_object( $content ) ) ? print_r( (array) $content, true ) : $content;

  if ( is_writable( $file ) ) {
    if ( ! $append ){
      $flag = null;
      $murmurations_log_buffer .= $log . "\n";
      $log = $murmurations_log_buffer;
    } else {
      $flag = FILE_APPEND;
    }
    return file_put_contents( $file, $log . "\n", $flag );
  } else {
    set_notice("Log file is not writable: " . $file, 'notice');
  }
}

function debug( $content , $meta = null ){

  $out = "<pre>";
  $out .= $meta ? $meta . ': ' : '';
  $out .=  ( is_array( $content ) || is_object( $content ) ) ? print_r( (array) $content, true ) : $content;
  $out .= "</pre>";

  if ( Config::get('debug_to_log') ){
    llog( $content, $meta );
  }

  return $out;

}

function error( $message, $severity = 'warn' ){
  llog( $message, 'error' );
  set_notice( $message, $severity );
  if($severity == 'fatal'){
    exit($message);
  }
}

function set_notice($message, $type = 'notice'){

  global $murmurations_agg_notices;

  $notices = $murmurations_agg_notices;

  $notices[] = array('message'=>$message,'type'=>$type);
  $_SESSION['murmurations_notices'] = $notices;

}

function get_notices(){
  global $murmurations_agg_notices;
  $notices = array();
  if(count($murmurations_agg_notices) > 0){
    $notices = $murmurations_agg_notices;
  }else if(isset($_SESSION['murmurations_notices'])){
    $notices = $_SESSION['murmurations_notices'];
  }
  unset($_SESSION['murmurations_notices']);
  return $notices;
}

function show_notices(){
  $notices = get_notices();
  foreach ($notices as $notice) {
    ?>
    <div class="notice notice-<?php echo $notice['type']; ?>">
				<p><?php echo $notice['message']; ?></p>
		</div>

    <?php
  }
}

?>
