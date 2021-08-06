<?php
namespace Murmurations\Aggregator;

class Node {

	private $errors = array();

	public function __construct( $arg = null ) {
    if( is_numeric($arg) ){
      $post = get_post( $arg );
      $this->buildFromWPPost( $post );
    } else if ( is_a( $arg, 'WP_Post' ) ) {
      $this->buildFromWPPost( $arg );
    } else if ( is_string( $arg ) ){
      $this->buildFromJson( $arg );
    }
	}

	public function buildFromJson( $json ) {
		$this->data = json_decode( $json, true );

		if ( ! $this->data ) {
			$this->error( 'Attempted to build from invalid JSON. Could not parse.' );
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

	public function buildFromWPPost( $p ) {

		if ( ! is_a( $p, 'WP_Post' ) ) {
			$this->error( 'Attempted to build from invalid WP Post.' );
			return false;
		}

		$this->data = $p->to_array();

		$metas = get_post_meta( $p->ID );

		foreach ( $metas as $key => $value ) {

			if ( substr( $key, 0, strlen( $this->config['meta_prefix'] ) ) == $this->config['meta_prefix'] ) {
				$key = substr( $key, strlen( $this->config['meta_prefix'] ) );
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


	public function checkFilters( array $filters ) {

		$matched = true;

		foreach ( $filters as $condition ) {
			if ( ! $this->checkCondition( $condition ) ) {
				$matched = false;
			}
		}

		return $matched;
	}

	private function checkCondition( array $condition ) {

    extract($condition);

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
				$meta_field = $this->config['meta_prefix'] . $key;
				update_post_meta( $id, $meta_field, $value );
			}

			return $id;

		}
	}

	public function getPostFromProfileUrl( $url, $args = null ) {

		$defaults = array(
			'post_type'  => 'murmurations_node',
			'meta_query' => array(
				array(
					'key'     => $this->config['meta_prefix'] . 'profile_url',
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

	private function error( $error ) {
		$this->errors[] = $error;
		llog( $error, 'Node error' );
	}

  public function hasErrors() {
    return count($this->errors) > 0;
  }

	public function getErrors() {
		return $this->errors;
	}

	public function getErrorsText() {
		$text = '';
		foreach ( $this->errors as $error ) {
			$text .= $error . "<br />\n";
		}
		return $text;
	}

	public function setProperty( $property, $value ) {
		$this->data[ $property ] = $value;
	}
}
