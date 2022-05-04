<?php
/**
 * Informal tests for aggregator functionality
 *
 * @package murmurations-aggregator
 */

// phpcs:disable

namespace Murmurations\Aggregator;

error_reporting( E_ALL );
ini_set( 'dispay_errors', true );
define( 'WP_USE_THEMES', false );
$base = dirname( dirname( __FILE__ ) );
require $base . '/../../wp-load.php';
if ( ! current_user_can( 'manage_options' ) ) {
	die( 'Permission denied' );
}

if ( $_GET['t'] ) {
	if ( $_GET['t'] && method_exists( 'Murmurations\Aggregator\Tests', $_GET['t'] ) ) {
		$f = $_GET['t'];
		if ( $_GET['p'] ) {
			$result = Tests::$f( $_GET['p'] );
		} else {
			$result = Tests::$f();
		}
		Tests::print( $result, $f . '(' . $p . ')' );
	}
}


class Tests {

	public static function node_update_filter_options(){
		return Node::update_filter_options();
	}

	public static function utils_input( $param, $method = 'GET', $filter = FILTER_DEFAULT ){
		$out = array(
			'input_param' => $param,
			'POST' => $_POST,
			'GET' => $_GET,
			'result' => Utils::input( $param, $method, $filter )
		);
		return $out;
	}
  public static function get_template_location( $template ){

    $result = Aggregator::get_template_location( $template );

    return array( Notices::get(), $result );

  }

  public static function get_index_nodes( ){

    $result = Aggregator::get_index_nodes();

    return array( Notices::get(), $result );

  }

  public static function update_node(){

		$data = array(
			'profile_url' => "https://www.open.coop/open.json",
			'last_updated' =>  123123123,
			'index_url' => "https://index.murmurations.network/v2/nodes",
		);

    $result = Aggregator::update_node( $data );

    return array($result, Notices::get());

  }

  public static function inspect_remote_node_json( $url ){

    $options = array(
      'api_basic_auth_user' => 'eco',
      'api_basic_auth_pass' => 'village',
      'api_key' => 'test_key'
    );

    $out = "Node JSON from: " . $url . "\n";

    $out .= Network::get_node_json( $url, $options );

    return $out;

  }

	public static function load_post_by_url( $primary_url = "open.coop" ){

		$p = Node::load_post_by_url( $primary_url );

		return array( Notices::get(), $p );

  }


  public static function inspect_local_node( $primary_url = "open.coop" ){

		$p = Node::load_post_by_url( $primary_url );

		$node = Node::build_from_wp_post( $p );

		return array( Notices::get(), $node );

  }

  public static function get_settings_fields(){
    return Settings::get_fields();
  }

  public static function schema(){
    return Schema::get();
  }

  public static function get_field_map(){
    return Schema::get_field_map();
  }

  public static function fetch_schema(){
    return Schema::fetch('http://localhost/projects/murmurations/wordpress-dev/wp-content/plugins/gen-region/schemas/gen_ecovillages_v0.0.1.json');
    return Schema::fetch('https://cdn.murmurations.network/schemas/murmurations_map-v1.json');
  }

  public static function dereference_schema(){
    $schema = Schema::fetch('https://cdn.murmurations.network/schemas/test_schema-v1.json');
    $a = array('include' => array('https://cdn.murmurations.network/schemas/murmurations_map-v1.json'));
    $s = array_replace_recursive($a, json_decode($schema, true) );
    return Schema::dereference( $s );
  }

	public static function update_feeds() {
		return Feeds::update_feeds();
	}

	public static function get_remote_feed_items( $url = 'https://murmurations.network/feed' ) {
		return Feeds::get_remote_feed_items( $url );
	}

	public static function get_feed( $url = 'https://murmurations.network/feed' ) {
		return Feeds::feed_request( $url );
	}

	public static function get_feed_url( $url = 'https://murmurations.network' ) {
		return Feeds::get_feed_url( $url );
	}

	public static function delete_all_feed_items() {
		return Feeds::delete_all_feed_items();
	}

	public static function test() {
		echo 'Testing test';
		return 'Testing test';
	}

	public static function debug( $message = 'Test debug message' ) {
		echo 'Testing debug';
		echo debug( $message );
	}

	public static function log( $message = 'Test log message' ) {
		$file = Config::get( 'log_file' );
		llog( $message );
		llog( 'Message 2' );
		$log_content = file_get_contents( $file );
		echo '<textarea style="width: 100%; height: 500px;">' . $log_content . '</textarea>';
	}

  public static function get_settings() {
    return Settings::get();
  }

	public static function get_index_json($index = 0) {

    $indices = Settings::get('indices');

    $index = $indices[$index];

		$url = $index['url'];

		$options['api_key']             =  $index['api_key'];
		$options['api_basic_auth_user'] = $index['api_basic_auth_user'];
		$options['api_basic_auth_pass'] = $index['api_basic_auth_pass'];

		$query = array(
		 'country' => 'Canada',
		// 'last_validated' => 1541779342
		);

		self::print( $url, 'URL' );
		self::print( $options, 'Options' );
		self::print( $query, 'Query' );

		$json = Network::get_index_json( $url, $query, $options );
    //$json = Network::getIndexJson( $url, array(), null);

		self::print( $json, 'Index result' );

	}

	public static function get_node_json( $value = 'node-identifier' ) {

		$url = 'https://node/path' . $value;

		$options['api_key'] = 'test_api_key';

		$json = Network::get_node_json( $url, $options );

		return json_decode( $json, true );

	}

	public static function basic_auth_test() {

		$url = 'https://test-index.murmurations.network/v1/nodes';

		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_USERPWD, 'user:pass' );

		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_FAILONERROR, true );

		$result = curl_exec( $ch );

		if ( $result === false ) {
			return 'No result returned from cURL request to index. cURL error: ' . curl_error( $ch );
		} else {
			return $result;
		}

	}

	public static function index_request( $params = null ) {
		$url = 'https://test-index.murmurations.network/v1/nodes';

		$query = array();
		if ( is_array( $params ) ) {
			foreach ( $params as $key => $value ) {
				$query[ $key ] = $value;
			}
		} else {
			$query = array(
				'test_param' => 'test_value',
			);
		}

		$fields_string = http_build_query( $query );

		$ch = curl_init();

		$user = 'test_api_key';
		$pass = null;

		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_USERPWD, $user . ':' . $pass );

		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_FAILONERROR, true );

		$result = curl_exec( $ch );

		if ( $result === false ) {
			echo 'No result returned from cURL request to index. cURL error:' . curl_error( $ch );
		}

		echo $result;

		echo '<pre>' . print_r( json_decode( $result, true ), true );

	}

	public static function print( $out, $name = null ) {
		echo '<pre>';
		echo $name ? $name . ': ' : '';
		echo ( is_array( $out ) || is_object( $out ) ) ? print_r( (array) $out, true ) : $out;
		echo '</pre>';
	}

}
