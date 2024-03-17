<?php

get_header(); ?>

<?php
function my_custom_content_before_post(): void {
	if ( is_single() ) {
		echo do_shortcode( '[murmurations_data title="Name" path="name"]' );
		echo do_shortcode( '[murmurations_data title="Nickname" path="nickname"]' );
		echo do_shortcode( '[murmurations_data title="Description" path="description"]' );
		echo do_shortcode( '[murmurations_data title="Primary URL" path="primary_url"]' );
		echo do_shortcode( '[murmurations_data title="Tags" path="tags"]' );
		echo do_shortcode( '[murmurations_data title="URLs" path="urls"]' );
		echo do_shortcode( '[murmurations_data title="Relationships" path="relationships"]' );
	}
}

add_action( 'astra_entry_content_before', 'my_custom_content_before_post' );
?>

<?php if ( astra_page_layout() == 'left-sidebar' ) : ?>

	<?php get_sidebar(); ?>

<?php endif ?>

<div id="primary" <?php astra_primary_class(); ?>>

	<?php astra_primary_content_top(); ?>

	<?php astra_content_loop(); ?>

	<?php astra_primary_content_bottom(); ?>

</div><!-- #primary -->

<?php if ( astra_page_layout() == 'right-sidebar' ) : ?>

	<?php get_sidebar(); ?>

<?php endif ?>

<?php get_footer(); ?>
