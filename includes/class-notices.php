<?php
/**
 * Notices class
 *
 * @package Murmurations Aggregator
 */

namespace Murmurations\Aggregator;

/**
 * Handle admin notices
 */
class Notices {
	/**
	 * Holds runtime notices
	 *
	 * @var $notices holds notices.
	 */
	public static $notices = array();
	/**
	 * Set a notices
	 *
	 * @param string $message  The notice message.
	 * @param string $type     Type of notice (notice, warning, error).
	 */
	public static function set( $message, $type = 'notice' ) {

		self::$notices[]                  = array(
			'message' => $message,
			'type'    => $type,
		);
		$_SESSION['murmurations_notices'] = self::$notices;

	}
	/**
	 * Get notices
	 *
	 * @return array array of notices.
	 */
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
	/**
	 * Show the notices in HTML container
	 */
	public static function show() {
		$notices = self::get();
		foreach ( $notices as $notice ) {
			?>
			<div class="notice notice-<?php Utils::e( $notice['type'] ); ?>">
				<p><?php Utils::e( $notice['message'] ); ?></p>
			</div>

			<?php
		}
	}

}
