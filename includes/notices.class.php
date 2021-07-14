<?php
namespace Murmurations\Aggregator;

/*
* Handle admin notifications
*/

class Notices {

	public static $notices = array();

	public static function set( $message, $type = 'notice' ) {

		self::$notices[]                  = array(
			'message' => $message,
			'type'    => $type,
		);
		$_SESSION['murmurations_notices'] = self::$notices;

	}

	public static function get() {
		$notices = array();
		if ( count( self::$notices ) > 0 ) {
			$notices = self::$notices;
		} elseif ( isset( $_SESSION['murmurations_notices'] ) ) {
			$notices = $_SESSION['murmurations_notices'];
		}
		unset( $_SESSION['murmurations_notices'] );
		return $notices;
	}

	public static function  show() {
		$notices = self::get();
		foreach ( $notices as $notice ) {
			?>
	  <div class="notice notice-<?php echo $notice['type']; ?>">
					<p><?php echo $notice['message']; ?></p>
			</div>

			<?php
		}
	}

}
