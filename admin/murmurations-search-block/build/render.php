<?php
/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

$tags      = isset( $_GET['tags'] ) ? sanitize_text_field( $_GET['tags'] ) : '';
$is_search = isset( $_GET['search_submit'] );

?>

<div <?php echo get_block_wrapper_attributes(); ?>>
    <form method="get">
        <label>
            <input type="text" name="tags" value="<?php echo esc_attr( $tags ); ?>" placeholder="tags">
        </label>
        <input type="submit" name="search_submit" value="Search">
    </form>

    <!-- Search Results -->
	<?php if ( $is_search && ! empty( $tags ) ): ?>
        <div class="search-results">
            <h3><?php esc_html_e( 'Search Results:', 'murmurations-search-block' ); ?></h3>
            <ul>
				<?php
				$route = '/murmurations-aggregator/v1/wp-nodes/search';
				$params = array('tags' => $tags);
				$request = new WP_REST_Request('GET', $route);
				$request->set_query_params($params);
				$response = rest_do_request($request);

				if ( $response->is_error() ) {
					?><p><?php esc_html_e( 'Error fetching posts.', 'murmurations-search-block' ); ?></p><?php
				} else {
					$posts = $response->get_data();
					if ( empty( $posts ) ) {
						?><p><?php esc_html_e( 'No posts found.', 'murmurations-search-block' ); ?></p><?php
					} else {
						foreach ( $posts as $post ) {
							?>
                            <li>
                            <a href="<?php echo esc_url( $post['permalink'] ); ?>"><?php echo esc_html( $post['post_title'] ); ?></a>
                            </li><?php
						}
					}
				}
				?>
            </ul>
        </div>
	<?php endif; ?>
</div>
