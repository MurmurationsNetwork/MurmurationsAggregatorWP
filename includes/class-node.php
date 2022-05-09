<?php
/**
 * Node class
 *
 * @package Murmurations Aggregator
 */

namespace Murmurations\Aggregator;

/**
 * Class for node objects
 */
class Node {
	/**
	 * Holds errors for the object.
	 *
	 * @var array $errors Holds errors for the object.
	 */
	private static $errors = array();

	/**
	 * Insert or update a local nodei nthe DB from an array of node data
	 *
	 * @param  array $profile the node data.
	 * @param  array $provenance information on where the profile came from.
	 * @return boolean true if successful, false on failure
	 */
	public static function upsert( array $profile, array $provenance ) {

		if ( ! $profile['primary_url'] && $profile['url'] ) {
			$profile['primary_url'] = $profile['url'];
		}

		// For profiles that have no primary_url, use the profile URL.
		if ( ! $profile['primary_url'] ) {
			$profile['primary_url'] = $profile['profile_url'];
		}

		$profile['primary_url'] = self::canonicalize_url( $profile['primary_url'] );

		$existing_post = self::load_post_by_url( $profile['primary_url'] );

		if ( $existing_post ) {
			$profile['post_id']     = $existing_post->ID;
			$profile['post_status'] = $existing_post->post_status;
			$existing_data          = get_post_meta( $existing_post->ID );
		}

		$id = self::save_post( $profile );

		if ( ! $id ) {
			self::error( 'Failed to save post' );
			return false;
		}

		foreach ( $profile as $field => $value ) {

			if ( $existing_post ) {
				if ( $existing_data[ Settings::get( 'meta_prefix' ) . $field ] ) {

					$existing_value      = maybe_unserialize( $existing_data[ Settings::get( 'meta_prefix' ) . $field ][0] );
					$existing_provenance = maybe_unserialize( $existing_data[ Settings::get( 'meta_provenance_prefix' ) . $field ][0] );

					// If there's an existing profile for this node, and the profile includes this field, and the version of
					// this field in the existing profile has a more authoritative provenance, don't use the new value for this field.
					if ( ! Field::compare_provenance( $profile['primary_url'], $existing_provenance, $provenance ) ) {
						continue;
					}
				}
			}

			self::update_field_value( $id, $field, $value, $provenance );

		}

		return true;

	}


	/**
	 * Build the node object from a WP post
	 *
	 * @param  WP_Post $p WP post object.
	 * @return boolean true if successful, false on failure
	 */
	public function build_from_wp_post( $p ) {

		if ( ! is_a( $p, 'WP_Post' ) ) {
			self::error( 'Attempted to build from invalid WP Post.' );
			return false;
		}

		$data = $p->to_array();

		$metas = get_post_meta( $p->ID );

		foreach ( $metas as $key => $value ) {

			if ( substr( $key, 0, strlen( Settings::get( 'meta_prefix' ) ) ) === Settings::get( 'meta_prefix' ) ) {
				$key = substr( $key, strlen( Settings::get( 'meta_prefix' ) ) );
			}

			$data[ $key ] = maybe_unserialize( $value[0] );
		}

		if ( is_array( $data['image'] ) ) {
			if ( isset( $data['image'][0]['url'] ) ) {
				$data['images'] = $data['image'];
				$data['image']  = $data['image'][0]['url'];
			}
		}

		if ( ! isset( $data['url'] ) && isset( $data['primary_url'] ) ) {
			$data['url'] = $data['primary_url'];
		}

		if ( ! $data['profile_url'] ) {
			self::error( 'Profile URL not found in WP Post data.' );
			return false;
		}

		return $data;

	}

	/**
	 * Check filter conditions against a node
	 *
	 * @param  array $node the node data.
	 * @param  array $filters the array of filters to check against.
	 * @return boolean true if all filters match, otherwise false.
	 */
	public function check_filters( array $node, array $filters ) {

		$matched = true;

		foreach ( $filters as $condition ) {
			if ( ! self::check_condition( $node, $condition ) ) {
				$matched = false;
			}
		}

		return $matched;
	}
	/**
	 * Check if this node matches a filter condition
	 *
	 * @param  array $node node data.
	 * @param  array $condition (array with field, comparison, and value).
	 * @return boolean True if condition is matched, false otherwise.
	 */
	private function check_condition( array $node, array $condition ) {

		$field       = $condition['field'];
		$comparision = $condition['comparison'];
		$value       = $condition['value'];

		if ( ! isset( $node[ $field ] ) ) {
			return false;
		}

		switch ( $comparison ) {
			case 'equals':
				// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison -- this should be non-strict
				if ( $node[ $field ] == $value ) {
					return true;
				}
				break;
			case 'isGreaterThan':
				if ( $node[ $field ] > $value ) {
					return true;
				}
				break;
			case 'isLessThan':
				if ( $node[ $field ] < $value ) {
					return true;
				}
				break;
			case 'isIn':
				if ( strpos( $value, $node[ $field ] ) !== false ) {
					return true;
				}
				break;
			case 'includes':
				if ( strpos( $node[ $field ], $value ) !== false ) {
					return true;
				}
				break;

			default:
				return false;
		}
	}

	/**
	 * Save the node to the DB
	 *
	 * @param array $data the node data.
	 * @return mixed ID of the post if successful, false on failure
	 */
	public function save_post( array $data ) {

		llog( $data, 'Saving post' );

		$fields = Schema::get_fields();

		$map = Schema::get_field_map();

		$wp_field_fallbacks = array(
			'post_title'   => array( 'name', 'title', 'url', 'profile_url' ),
			'post_content' => array( 'description', 'name', 'title', 'url', 'profile_url' ),
		);

		$post_data = array();

		foreach ( $fields as $field => $attribs ) {

			if ( $map[ $field ]['callback'] ) {
				if ( is_callable( $map[ $field ]['callback'] ) ) {
					$value = call_user_func( $map[ $field ]['callback'], $data[ $field ], $field );
				} else {
					self::error( 'Un-callable callback in field map: ' . $map[ $field ]['callback'] );
				}
			}

			if ( $map[ $field ]['post_field'] ) {
				$post_data[ $map[ $field ]['post_field'] ] = $data[ $field ];
			}
		}

		foreach ( $wp_field_fallbacks as $f => $sources ) {
			if ( ! $post_data[ $f ] ) {
				foreach ( $sources as $s ) {
					if ( $data[ $s ] ) {
						$post_data[ $f ] = $data[ $s ];
						break;
					}
				}
			}
		}

		$post_data['post_type'] = 'murmurations_node';

		// This method should only be called once the check for an existing post has already been done.
		// If there is an existing post, the 'post_id' parameter will be set.
		if ( $data['post_id'] ) {
			$post_data['ID'] = $data['post_id'];
			if ( Settings::get( 'updated_node_post_status' ) === 'no_change' ) {
				// wp_insert_post defaults to 'draft' status, even on existing published posts!
				$post_data['post_status'] = $data['post_status'];
			} else {
				$post_data['post_status'] = Settings::get( 'updated_node_post_status' );
			}
		} else {
			$post_data['post_status'] = Settings::get( 'new_node_post_status' );
		}

		$result = wp_insert_post( $post_data, true );

		if ( false === $result ) {
			$this->error( 'Failed to insert post.' );
			return false;
		} else {

			true === $result ? $id = $post_data['ID'] : $id = $result;

			return $id;

		}
	}
	/**
	 * Update a specific meta value for a node
	 *
	 * @param  int    $post_id ID of the node post.
	 * @param  string $field field name.
	 * @param  mixed  $value new value.
	 * @param  array  $provenance provenance information for the new data.
	 */
	public static function update_field_value( int $post_id, string $field, $value, $provenance = null ) {
		llog( $provenance, 'Updating field value with provenance' );
		update_post_meta( $post_id, Settings::get( 'meta_prefix' ) . $field, $value );
		if ( $provenance ) {
			update_post_meta( $post_id, Settings::get( 'meta_provenance_prefix' ) . $field, $provenance );
		}
	}
	/**
	 * Convert a URL to its canonical form
	 *
	 * @param  string $url Input URL.
	 * @return string The canonicalized URL.
	 */
	public static function canonicalize_url( string $url ) {

		// If URL includes "://", split and use only right part (remove scheme).
		if ( strpos( $url, '://' ) !== false ) {
			$url = explode( '://', $url )[1];
		}

		// Remove leading www if present.
		if ( substr( $url, 0, 4 ) === 'www.' ) {
			$url = substr( $url, 4 );
		}

		// Remove trailing slash if present.
		if ( substr( $url, -1 ) === '/' ) {
			$url = substr( $url, 0, -1 );
		}

		return $url;

	}


	/**
	 * Get the node post that match a primary URL
	 *
	 * @param string $url the primary_url of the node.
	 * @param array  $args additional args for the post query.
	 * @return mixed WP_Post object if successful, false on failure.
	 */
	public static function load_post_by_url( $url, $args = null ) {

		$url = self::canonicalize_url( $url );

		llog( $url, 'Loading post from canonicalized URL' );

		$defaults = array(
			'post_type'   => 'murmurations_node',
			'meta_query'  => array( // phpcs:ignore
				array(
					'key'     => Settings::get( 'meta_prefix' ) . 'primary_url',
					'value'   => $url,
					'compare' => '=',
				),
			),
			// Default to all post statuses, even trashed.
			'post_status' => array_keys( get_post_stati() ),
		);

		$args = wp_parse_args( $args, $defaults );

		$posts = get_posts( $args );

		if ( count( $posts ) > 0 ) {
			return $posts[0];
		} else {
			return false;
		}

	}

	/**
	 * Delete this node from the DB
	 *
	 * @param int $id The ID of the node post to be deleted.
	 */
	public function delete( int $id ) {
		if ( $id ) {
			$result = wp_delete_post( $id );
		}
		if ( $result ) {
			return true;
		} else {
			self::error( 'Failed to delete node: ' . $id );
			return false;
		}
	}


	/**
	 * Update list of local values for filters
	 *
	 * @return boolean true if successful, false on failure.
	 */
	public function update_filter_options() {
		$filter_fields = Settings::get( 'filter_fields' );
		$options = array();
		global $wpdb;
		foreach ( $filter_fields as $field ) {
			$meta_values = $wpdb->get_results(
				$wpdb->prepare( "SELECT meta_value FROM $wpdb->postmeta where meta_key = %s", Settings::get( 'meta_prefix' ) . $field ),
				ARRAY_A
			);

			foreach ( $meta_values as $key => $row ) {
				$value = maybe_unserialize( $row['meta_value'] );
				if ( is_array( $value ) ) {
					foreach ( $value as $item ) {
						$options[ $field ][] = $item;
					}
				}else{
					$options[ $field ][] = $value;
				}

			}
			if ( isset( $options[ $field ] ) ){
				$options[ $field ] = array_unique( $options[ $field ] );
			}
		}

		// Strangely, if the updated value is the same as the current value, update_option
		// returns false. So, in order to know what's actually going on, check the value first.

		$existing = get_option('murmurations_aggregator_filter_options');

		if ( $existing === $options ) {
			$result = true;
		} else {
			$result = update_option( 'murmurations_aggregator_filter_options', $options );
		}

		if ( $result ) {
			return true;
		} else {
			self::error( 'Failed to update filter options' );
			return false;
		}
	}

	/**
	 * Deactivate this node (set status to draft)
	 *
	 * @param int $id The ID of the node post to deactive.
	 * @return boolean true if successful, false on failure.
	 */
	public function deactivate( int $id ) {
		$result = wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'draft',
			)
		);
		if ( $result ) {
			return true;
		} else {
			self::error( 'Failed to deactivate node: ' . $id );
			return false;
		}
	}

	/**
	 * Set an error on this node
	 *
	 * @param string $error The error message.
	 */
	private function error( $error ) {
		self::$errors[] = $error;
		llog( $error, 'Node error' );
	}

	/**
	 * Find out if there are errors
	 *
	 * @return boolean true if errors, otherwise false.
	 */
	public function has_errors() {
		return count( self::$errors ) > 0;
	}

	/**
	 * Get the array of errors
	 *
	 * @return array Error array.
	 */
	public function get_errors() {
		return self::$errors;
	}

	/**
	 * Get the text of errors
	 *
	 * @return string HTML errors string.
	 */
	public function get_errors_text() {
		$text = '';
		foreach ( self::$errors as $key => $error ) {
			$text .= $error . "<br />\n";
			unset( self::$errors[ $key ] );
		}
		return $text;
	}
}
