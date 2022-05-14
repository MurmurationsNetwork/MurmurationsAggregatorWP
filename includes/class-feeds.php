<?php
/**
 * Feeds class
 *
 * @package Murmurations Aggregator
 */

namespace Murmurations\Aggregator;

/**
 * Class that handles collecting, parsing, and displaying RSS feed data from nodes
 */
class Feeds {
	/**
	 * Initialize
	 */
	public static function init() {

		require_once MURMAG_ROOT_PATH . 'libraries/Feed.php';

		add_action(
			'murmurations_feed_update',
			array( 'Murmurations\Aggregator\Feeds', 'update_feeds' )
		);

		add_action(
			'init',
			array( 'Murmurations\Aggregator\Feeds', 'register_type_taxes' )
		);

		add_shortcode(
			Config::get( 'plugin_slug' ) . '-feeds',
			array( 'Murmurations\Aggregator\Feeds', 'show_feeds' )
		);

	}
	/**
	 * Register CPT and taxonomies
	 */
	public static function register_type_taxes() {

		register_post_type(
			'murms_feed_item',
			array(
				'labels'        => array(
					'name'          => Config::get( 'plugin_name' ) . ' Feed Items',
					'singular_name' => Config::get( 'plugin_name' ) . ' Feed Item',
				),
				'public'        => true,
				'has_archive'   => true,
				'menu_icon'     => 'dashicons-rss',
				'show_in_menu'  => true,
				'menu_position' => 21,
				'rewrite'       => array( 'slug' => 'murmurations-feed-item' ),
			)
		);

		register_taxonomy(
			'murms_feed_item_tag',
			'murms_feed_item',
			array(
				'labels'            => array(
					'name'          => __( 'Tags' ),
					'singular_name' => __( 'Tag' ),
				),
				'show_admin_column' => true,
			)
		);

		register_taxonomy(
			'murms_feed_item_source',
			'murms_feed_item',
			array(
				'labels'            => array(
					'name'          => __( 'Sources' ),
					'singular_name' => __( 'From' ),
				),
				'show_admin_column' => true,
			)
		);
	}
	/**
	 * Save feed item to the DB
	 *
	 * @param  array $item_data Data for the item.
	 */
	public static function save_feed_item( $item_data ) {

		if ( ! $item_data['url'] && $item_data['link'] ) {
			$item_data['url'] = $item_data['link'];
		}

		$post_data = array();

		$content_allowed_tags = array( 'a', 'p', 'div', 'ul', 'li', 'img' );

		$post_data['post_title']   = wp_strip_all_tags( $item_data['title'] );
		$post_data['post_content'] = strip_tags( $item_data['content:encoded'], $content_allowed_tags );
		if ( ! $post_data['post_content'] ) {
			$post_data['post_content'] = $item_data['title'];
		}

		// Get the images if possible.
		preg_match( '/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', $item_data['description'], $image );

		if ( $image['src'] ) {
			$item_data['image'] = $image['src'];
		}

		$post_data['post_excerpt'] = substr( wp_strip_all_tags( $item_data['description'] ), 0, 300 ) . '...';

		if ( ! $post_data['post_excerpt'] ) {
			$post_data['post_excerpt'] = substr( $post_data['post_content'], 0, 300 ) . '...';
		}

		$post_data['post_type'] = 'murms_feed_item';
		// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		$post_data['post_date'] = date( 'Y-m-d H:i:s', strtotime( $item_data['pubDate'] ) );

		$tags = $item_data['category'];

		// Check if node exists. If yes, update using existing post ID.
		$existing_post = self::load_feed_item( $item_data['url'] );

		if ( $existing_post ) {
			$post_data['ID']          = $existing_post->ID;
			$post_data['post_status'] = $existing_post->post_status;
		} else {
			$post_data['post_status'] = Settings::get( 'default_feed_item_status' );
		}

		$result = wp_insert_post( $post_data, true );

		if ( false === $result ) {
			llog( $result, 'Failed to insert feed item post' );
		} else {
			llog( $result, 'Inserted feed item post' );
			true === $result ? $id = $post_data['ID'] : $id = $result;

			// Add terms directly.
			wp_set_object_terms( $id, $tags, 'murms_feed_item_tag' );
			wp_set_object_terms( $id, array( $item_data['node_name'] ), 'murms_feed_item_source' );

			// And use the ID to update meta.
			update_post_meta( $id, 'murmurations_feed_item_url', $item_data['url'] );
			update_post_meta( $id, 'murmurations_feed_item_data', $item_data );

		}
	}
	/**
	 * Delete all the locally stored feed items
	 */
	public function delete_all_feed_items() {

		$args = array(
			'post_type'      => 'murms_feed_item',
			'post_status'    => 'all',
			'posts_per_page' => -1,
		);

		$posts = get_posts( $args );

		foreach ( $posts as $post ) {
			wp_delete_post( $post->ID, true );
		}

	}

	/**
	 *  Load a murmurations_feed_item post from DB
	 *
	 * @param  string $url the feed item URL.
	 * @return mixed WP_Post or false on failure
	 */
	public function load_feed_item( $url ) {

		$args = array(
			'post_type'   => 'murms_feed_item',
			'post_status' => array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash' ),

			'meta_query'  => array( // phpcs:ignore -- ignore the slowness of meta queries, since there's no alternative
				array(
					'key'     => 'murmurations_feed_item_url',
					'value'   => $url,
					'compare' => '=',
				),
			),
		);

		$posts = get_posts( $args );

		if ( count( $posts ) > 0 ) {
			return $posts[0];
		} else {
			return false;
		}
	}
	/**
	 * Update the feed URL for a node
	 *
	 * @param  array $node node data.
	 * @return array the updated node data
	 */
	public static function update_node_feed_url( $node ) {
		if ( ! isset( $node['feed_url'] ) && isset( $node['primary_url'] ) ) {
			$feed_url = self::get_feed_url( $node['primary_url'] );
			if ( ! $feed_url ) {
				$feed_url = 'not_found';
			}
			Node::update_field_value( $node, 'feed_url', $feed_url );
		}
		return $node;
	}
	/**
	 * Update feed URLs for all locally stored nodes
	 */
	public static function update_feed_urls() {
		$nodes = Aggregator::get_nodes();
		foreach ( $nodes as $id => $node ) {
			if ( ! isset( $node['feed_url'] ) && isset( $node['primary_url'] ) ) {
				$feed_url = self::get_feed_url( $node['primary_url'] );
				if ( ! $feed_url ) {
					$feed_url = 'not_found';
				}
				Node::update_field_value( $node, 'feed_url', $feed_url );
			}
		}
	}

	/**
	 * Parse the feed URL out of a node website
	 *
	 * @param  string $node_url The URL of the node.
	 * @return mixed the feed URL or false if a URL was not found.
	 */
	public static function get_feed_url( $node_url ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( file_get_contents( $node_url ) ) {
			preg_match_all(
				'/<link\srel\=\"alternate\"\stype\=\"application\/(?:rss|atom)\+xml\"\stitle\=\".*href\=\"(.*)\"\s\/\>/',
				file_get_contents( $node_url ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				$matches
			);
			return $matches[1][0];
		}
		return false;
	}
	/**
	 * Update all the feeds for locally stored nodes
	 *
	 * @return array stats on the results.
	 */
	public static function update_feeds() {

		self::delete_all_feed_items();

		$feed_items = array();

		// Get the locally stored nodes.
		$nodes = Aggregator::get_nodes();

		$results = array(
			'nodes_with_feeds'   => 0,
			'feed_items_fetched' => 0,
			'feed_items_saved'   => 0,
		);

		foreach ( $nodes as $node ) {

			if ( ! $node['feed_url'] ) {
				$node = self::update_node_feed_url( $node );
			}

			if ( $node['feed_url'] && 'not_found' !== $node['feed_url'] ) {
				$node_feed_items = self::get_remote_feed_items( $node['feed_url'] );

				if ( count( $node_feed_items ) > 0 ) {
					$results['nodes_with_feeds']++;

					foreach ( $node_feed_items as $key => $item ) {

						$item['node_name'] = $node['name'];
						$item['node_url']  = $node['primary_url'];

						$feed_items[] = $item;

					}
				}
			}
		}

		// Sort reverse chronologically.
		usort(
			$feed_items,
			function( $a, $b ) {
				return $b['timestamp'] - $a['timestamp'];
			}
		);

		$results['feed_items_fetched'] = count( $feed_items );

		foreach ( $feed_items as $key => $item ) {

			$result = self::save_feed_item( $item );

			$results['feed_items_saved']++;
			// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison -- (should be loose comparison)
			if ( Settings::get( 'max_feed_items' == $results['feed_items_saved'] ) ) {
				break;
			}
		}

		Notices::set( 'Feeds updated. ' . $results['feed_items_fetched'] . ' feed items fetched from ' . $results['nodes_with_feeds'] . ' nodes. ' . $results['feed_items_saved'] . ' feed items saved.', 'success' );

		return $results;

	}
	/**
	 * Get the items from a feed URL
	 *
	 * @param  string $feed_url The URL of the feed.
	 * @return array The feed items
	 */
	public static function get_remote_feed_items( $feed_url ) {

		$feed = self::feed_request( $feed_url );

		$items = array();

		if ( is_array( $feed ) ) {

			// This comes with an *xml key.
			$feed = array_shift( $feed );

			/*
			RSS includes multiple <item> elements. The RSS parser adds a single ['item'],
			with numerically indexed elements for each item from the RSS.
			But, if there is only one item in the feed, it doesn't do this, and ['item']
			is an array of item properties, not items.
			*/

			if ( ! $feed['item'][0] ) {
				$temp = $feed['item'];
				unset( $feed['item'] );
				$feed['item'][0] = $temp;
			}

			foreach ( $feed['item'] as $item ) {
				if ( is_array( $item ) ) {
					$items[] = $item;
					// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison -- should be loose comparison
					if ( count( $items ) == Settings::get( 'max_feed_items_per_node' ) ) {
						break;
					}
				} else {
					llog( $feed, 'Strange non-array feed item.' );
				}
			}
		} else {
			llog( "This feed could not be parsed: $feed_url" );
			Notices::set( "This feed could not be parsed: $feed_url", 'warning' );
		}

		return $items;

	}
	/**
	 * Download and parse the XML feed data from a URL using the SimpleXML class
	 *
	 * @param  string $url The feed URL.
	 * @return array|boolean Array of feed items or false on error.
	 */
	public static function feed_request( $url ) {

		// Get simpleXML of feed.
		try {
			$rss = \Feed::loadRss( $url );
			return self::xml_to_array( $rss );
		} catch ( \Exception $e ) {
			llog( $e, 'Error loading feed' );
			error( "Couldn't load feed" );
			return false;
		}
	}
	/**
	 * Turn an XML object into an associative array
	 *
	 * @param  SimpleXML $xml_obj the XML object.
	 * @param  array     $out array of items to append to.
	 * @return array     array output
	 */
	public static function xml_to_array( $xml_obj, $out = array() ) {
		foreach ( (array) $xml_obj as $index => $node ) {
			$out[ $index ] = ( is_object( $node ) || is_array( $node ) ) ? self::xml_to_array( $node ) : $node;
		}
		return $out;
	}

}