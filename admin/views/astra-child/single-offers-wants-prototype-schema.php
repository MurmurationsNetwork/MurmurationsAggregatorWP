<?php

get_header(); ?>

<?php
function my_custom_content_before_post(): void {
	if ( is_single() ) {
		echo do_shortcode( '[murmurations_data title="Exchange Type" path="exchange_type"]' );
		echo do_shortcode( '[murmurations_data title="Item Type" path="item_type"]' );
		echo do_shortcode( '[murmurations_data title="Tags" path="tags"]' );
		echo do_shortcode( '[murmurations_data title="Title" path="title"]' );
		echo do_shortcode( '[murmurations_data title="Description" path="description"]' );
		echo do_shortcode( '[murmurations_data title="Geolocation Scope" path="geolocation"]' );
		echo do_shortcode( '[murmurations_data title="Geographic Scope" path="geographic_scope"]' );
		echo do_shortcode( '[murmurations_data title="Contact Details" path="contact_details.email" second_path="contact_details.contact_form"]' );
		echo do_shortcode( '[murmurations_data title="Transaction Type" path="transaction_type"]' );
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