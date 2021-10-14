<?php
/**
 * Node class
 *
 * @package Murmurations Aggregator
 */

namespace Murmurations\Aggregator;

/**
 * OOP class for node objects
 */
class Node {
	/**
	 * @var array $errors Holds errors for the object.
	 */
	private $errors = array();

	/**
	 * Constructor
	 *
	 * @param mixed $arg Can be:
	 * a post ID int (in which case will attempt to build from the post).
	 * a post object (build from the post).
	 * a JSON string (build from JSON, inserting or updating the post record).
	 */
	public function __construct( $arg = null ) {
		if ( is_numeric( $arg ) ) {
			$post = get_post( $arg );
			$this->buildFromWPPost( $post );
		} elseif ( is_a( $arg, 'WP_Post' ) ) {
			$this->buildFromWPPost( $arg );
		} elseif ( is_array( $arg ) ) {
			$this->buildFromArray( $arg );
		}
	}

	/**
	 * Build a local node from profile JSON
	 *
	 * @param  string $json Node profile JSON
	 * @return boolean true on success, false on failure
	 */
	public function buildFromArray( $profile_array ) {
		$this->data = $profile_array;

		if ( ! $this->data ) {
			$this->error( 'Attempted to build from invalid JSON. Could not parse.');
      llog( $json, "Failed to parse node JSON");
			return false;
		}

		if ( ! $this->data['profile_url'] ) {
			$this->error( 'Attempted to build from invalid node data. Profile URL not found.' );
			llog( $this->data, 'Node data missing profile url' );
			return false;
		}

		$this->url = $this->data['profile_url'];

		$existing_post = $this->getPostFromProfileUrl(
			$this->url,
			array( 'post_status' => 'any' )
		);

		if ( $existing_post ) {
			$this->ID = $existing_post->ID;
		}

		return true;

	}
	/**
	 * Build the node object from a WP post
	 *
	 * @param  WP_Post $p WP post object
	 * @return boolean true if successful, false on failure
	 */
	public function buildFromWPPost( $p ) {

		if ( ! is_a( $p, 'WP_Post' ) ) {
			$this->error( 'Attempted to build from invalid WP Post.' );
			return false;
		}

		$this->data = $p->to_array();

		$metas = get_post_meta( $p->ID );

		foreach ( $metas as $key => $value ) {

			if ( substr( $key, 0, strlen( Settings::get( 'meta_prefix' ) ) ) === Settings::get( 'meta_prefix' ) ) {
				$key = substr( $key, strlen( Settings::get( 'meta_prefix' ) ) );
			}

			$this->data[ $key ] = maybe_unserialize( $value[0] );
		}

		if ( ! $this->data['profile_url'] ) {
			$this->error( 'Profile URL not found in WP Post data.' );
			return false;
		}

		$this->url = $this->data['profile_url'];

		return true;

	}

	/**
	 * Check filter conditions against the post object
	 *
	 * @param  array $filters the array of filters to check against.
	 * @return boolean true if all filters match, otherwise false.
	 */
	public function checkFilters( array $filters ) {

		$matched = true;

		foreach ( $filters as $condition ) {
			if ( ! $this->checkCondition( $condition ) ) {
				$matched = false;
			}
		}

		return $matched;
	}
  /**
   * Check if this node matches a filter condition
   *
   * @param  array  $condition (array with field, comparison, and value).
   * @return boolean True if condition is matched, false otherwise.
   */
	private function checkCondition( array $condition ) {

		extract( $condition );

		if ( ! isset( $this->data[ $field ] ) ) {
			return false;
		}

		switch ( $comparison ) {
			case 'equals':
				if ( $this->data[ $field ] == $value ) {
					return true;
				}
				break;
			case 'isGreaterThan':
				if ( $this->data[ $field ] > $value ) {
					return true;
				}
				break;
			case 'isLessThan':
				if ( $this->data[ $field ] < $value ) {
					return true;
				}
				break;
			case 'isIn':
				if ( strpos( $value, $this->data[ $field ] ) !== false ) {
					return true;
				}
				break;
			case 'includes':
				if ( strpos( $this->data[ $field ], $value ) !== false ) {
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
	 * @return mixed ID of the post if successful, false on failure
	 */
	public function save() {

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
					$value = call_user_func( $map[ $field ]['callback'], $this->data[ $field ], $field );
				} else {
					$this->error( 'Un-callable callback in field map: ' . $map[ $field ]['callback'] );
				}
			}

			if ( $map[ $field ]['post_field'] ) {
				$post_data[ $map[ $field ]['post_field'] ] = $this->data[ $field ];
			}
		}

		foreach ( $wp_field_fallbacks as $f => $sources ) {
			if ( ! $post_data[ $f ] ) {
				foreach ( $sources as $s ) {
					if ( $this->data[ $s ] ) {
						$post_data[ $f ] = $this->data[ $s ];
						break;
					}
				}
			}
		}

		$node_data = $this->data;

		$post_data['post_type'] = 'murmurations_node';

		$existing_post = $this->getPostFromProfileUrl(
			$node_data['profile_url'],
			array( 'post_status' => 'any' )
		);

		if ( $existing_post ) {
			$post_data['ID'] = $existing_post->ID;
			if ( Settings::get( 'updated_node_post_status' ) == 'no_change' ) {
				// wp_insert_post defaults to 'draft' status, even on existing published posts!
				$post_data['post_status'] = $existing_post->post_status;
			} else {
				$post_data['post_status'] = Settings::get( 'updated_node_post_status' );
			}
		} else {
			$post_data['post_status'] = Settings::get( 'new_node_post_status' );
		}

		$result = wp_insert_post( $post_data, true );

		if ( $result === false ) {
			$this->error( 'Failed to insert post.' );
			return false;
		} else {

			$result === true ? $id = $post_data['ID'] : $id = $result;

			foreach ( $node_data as $key => $value ) {
				$meta_field = Settings::get( 'meta_prefix' ) . $key;
				update_post_meta( $id, $meta_field, $value );
			}

			return $id;

		}
	}

	/**
	 * Get the node post from the profile URL of the node
	 *
	 * @param string $url the profile URL of the node.
	 * @param array  $args additional args for the post query.
	 * @return mixed WP_Post object if successful, false on failure.
	 */
	public function getPostFromProfileUrl( $url, $args = null ) {

		$defaults = array(
			'post_type'  => 'murmurations_node',
			'meta_query' => array(
				array(
					'key'     => Settings::get( 'meta_prefix' ) . 'profile_url',
					'value'   => $url,
					'compare' => '=',
				),
			),
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
	 */
	public function delete() {
		if ( $this->ID ) {
			$result = wp_delete_post( $this->ID );
		}
		if ( $result ) {
			return true;
		} else {
			$this->error( 'Failed to delete node: ' . $this->ID );
			return false;
		}
	}

	/**
	 * Deactivate this node (set status to draft)
	 */
	public function deactivate() {
		if ( $this->ID ) {
			$result = wp_update_post(
				array(
					'ID'          => $this->ID,
					'post_status' => 'draft',
				)
			);
			if ( $result ) {
				  return true;
			} else {
				$this->error( 'Failed to deactivate node: ' . $this->ID );
				return false;
			}
		}
	}

	/**
	 * Set an error on this node
	 *
	 * @param string $error The error message.
	 */
	private function error( $error ) {
		$this->errors[] = $error;
		llog( $error, 'Node error' );
	}

	/**
	 * Find out if there are errors
	 *
	 * @return boolean true if errors, otherwise false.
	 */
	public function hasErrors() {
		return count( $this->errors ) > 0;
	}

	/**
	 * Get the array of errors
	 *
	 * @return array Error array.
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Get the text of errors
	 *
	 * @return string HTML errors string.
	 */
	public function getErrorsText() {
		$text = '';
		foreach ( $this->errors as $error ) {
			$text .= $error . "<br />\n";
		}
		return $text;
	}

	/**
	 * Set a property of the node object
	 *
	 * @param string $property property name.
	 * @param mixed  $value The property value.
	 */
	public function setProperty( $property, $value ) {
		$this->data[ $property ] = $value;
	}
}
