<?php
namespace Murmurations\Aggregator;

class Feeds {

	public static $wpagg;

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
				// 'show_in_menu' => 'murmurations-aggregator-settings',
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
	}

	public static function save_feed_item( $item_data ) {

		if ( ! $item_data['url'] && $item_data['link'] ) {
			$item_data['url'] = $item_data['link'];
		}

		$post_data = array();

		$post_data['post_title']   = $item_data['title'];
		$post_data['post_content'] = $item_data['content:encoded'];
		if ( ! $post_data['post_content'] ) {
			$post_data['post_content'] = $item_data['title'];
		}

		// Get the images if possible
		preg_match( '/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', $item_data['description'], $image );

		if ( $image['src'] ) {
			$item_data['image'] = $image['src'];
		}

		$post_data['post_excerpt'] = substr( strip_tags( $item_data['description'] ), 0, 300 ) . '...';

		if ( ! $post_data['post_excerpt'] ) {
			$post_data['post_excerpt'] = substr( $post_data['post_content'], 0, 300 ) . '...';
		}

		$post_data['post_type'] = 'murms_feed_item';
		$post_data['post_date'] = date( 'Y-m-d H:i:s', strtotime( $item_data['pubDate'] ) );

		$tags = $item_data['category'];

		// Check if node exists. If yes, update using existing post ID
		$existing_post = self::load_feed_item( $item_data['url'] );

		if ( $existing_post ) {
			$post_data['ID']          = $existing_post->ID;
			$post_data['post_status'] = $existing_post->post_status;
		} else {
			$post_data['post_status'] = Settings::get( 'default_feed_item_status' );
			echo llog( $post_data['post_status'], 'Saving with post status' );
		}

		$result = wp_insert_post( $post_data, true );

		if ( $result === false ) {
			llog( $result, 'Failed to insert feed item post' );
		} else {
			llog( $result, 'Inserted feed item post' );
			$result === true ? $id = $post_data['ID'] : $id = $result;

			// Add terms directly
			$tresult = wp_set_object_terms( $id, $tags, 'murms_feed_item_tag' );

			// And use the ID to update meta
			update_post_meta( $id, 'murmurations_feed_item_url', $item_data['url'] );
			update_post_meta( $id, 'murmurations_feed_item_data', $item_data );

		}
	}

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

	/* Load a murmurations_feed_item post from WP */

	public function load_feed_item( $url ) {

		$args = array(
			'post_type'   => 'murms_feed_item',
			'post_status' => array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash' ),

			'meta_query'  => array(
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

	public static function update_node_feed_url( $node ) {
		if ( ! isset( $node->data['feed_url'] ) && isset( $node->data['url'] ) ) {
			$feed_url = self::get_feed_url( $node->data['url'] );
			if ( ! $feed_url ) {
				$feed_url = 'not_found';
			}
			$node->setProperty( 'feed_url', $feed_url );
			$node->save();
		}
		return $node;
	}

	public static function update_feed_urls() {
		$nodes = self::$wpagg->load_nodes();
		foreach ( $nodes as $id => $node ) {
			if ( ! isset( $node->data['feed_url'] ) && isset( $node->data['url'] ) ) {
				$feed_url = self::get_feed_url( $node->data['url'] );
				if ( ! $feed_url ) {
					$feed_url = 'not_found';
				}
				$node->setProperty( 'feed_url', $feed_url );
				$node->save();
			}
		}
	}


	public static function get_feed_url( $node_url ) {
		if ( @file_get_contents( $node_url ) ) {
			preg_match_all( '/<link\srel\=\"alternate\"\stype\=\"application\/(?:rss|atom)\+xml\"\stitle\=\".*href\=\"(.*)\"\s\/\>/', file_get_contents( $node_url ), $matches );
			return $matches[1][0];
		}
		return false;
	}

	public static function update_feeds() {

		self::delete_all_feed_items();

		$feed_items = array();

		// Get the locally stored nodes
		self::$wpagg->load_nodes();

		$nodes = self::$wpagg->nodes;

		$results = array(
			'nodes_with_feeds'   => 0,
			'feed_items_fetched' => 0,
			'feed_items_saved'   => 0,
		);

		foreach ( $nodes as $node ) {

			if ( ! $node->data['feed_url'] ) {
				$node = self::update_node_feed_url( $node );
			}

			if ( $node->data['feed_url'] ) {
				$node_feed_items = self::get_remote_feed_items( $node->data['feed_url'] );

				if ( count( $node_feed_items ) > 0 ) {
					$results['nodes_with_feeds']++;

					foreach ( $node_feed_items as $key => $item ) {

						$item['node_name'] = $node->data['name'];
						$item['node_url']  = $node->data['url'];

						$feed_items[] = $item;

					}
				}
			}
		}

		// Sort reverse chronologically
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
			if ( $results['feed_items_saved'] == Settings::get( 'max_feed_items' ) ) {
				break;
			}
		}

		Notices::set( 'Feeds updated. ' . $results['feed_items_fetched'] . ' feed items fetched from ' . $results['nodes_with_feeds'] . ' nodes. ' . $results['feed_items_saved'] . ' feed items saved.', 'success' );

		return $results;

	}

	public static function get_remote_feed_items( $feed_url ) {

		$feed = self::feed_request( $feed_url );

		$items = array();

		if ( is_array( $feed ) ) {

			// This comes with an *xml key
			$feed = array_shift( $feed );

			/*
			RSS includes multiple <item> elements. The RSS parser adds a single ['item'],
			with numerically indexed elements for each item from the RSS. But, if there is only one item in the feed, it doesn't do this, and ['item'] is an array of item properties, not items */

			if ( ! $feed['item'][0] ) {
				$temp = $feed['item'];
				unset( $feed['item'] );
				$feed['item'][0] = $temp;
			}

			foreach ( $feed['item'] as $item ) {
				if ( is_array( $item ) ) {
					$items[] = $item;
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

	public static function feed_request( $url ) {

		// Get simpleXML of feed
		try {
			$rss = \Feed::loadRss( $url );
			return self::xml_to_array( $rss );
		} catch ( \Exception $e ) {
			llog( $e, 'Error loading feed' );
			error( "Couldn't load feed" );
			return false;
		}
	}

	public static function xml_to_array( $xmlObj, $out = array() ) {
		foreach ( (array) $xmlObj as $index => $node ) {
			$out[ $index ] = ( is_object( $node ) || is_array( $node ) ) ? self::xml_to_array( $node ) : $node;
		}
		return $out;
	}

}
