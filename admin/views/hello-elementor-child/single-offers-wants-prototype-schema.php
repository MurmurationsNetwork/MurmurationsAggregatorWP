<?php
/**
 * The template for displaying singular post-types: posts, pages and user-defined custom post types.
 *
 * @package HelloElementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

get_header();

while ( have_posts() ) :
	the_post();
	?>

    <main id="content" <?php post_class( 'site-main' ); ?>>

		<?php if ( apply_filters( 'hello_elementor_page_title', true ) ) : ?>
            <header class="page-header">
				<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
            </header>
		<?php endif; ?>

        <div class="page-content">
			<?php
			echo do_shortcode( '[murmurations_image path="image"]' );
			echo do_shortcode( '[murmurations_data title="Exchange Type" path="exchange_type"]' );
			echo do_shortcode( '[murmurations_data title="Item Type" path="item_type"]' );
			echo do_shortcode( '[murmurations_data title="Tags" path="tags"]' );
			echo do_shortcode( '[murmurations_data title="Title" path="title"]' );
			echo do_shortcode( '[murmurations_data title="Description" path="description"]' );
			echo do_shortcode( '[murmurations_data title="Geolocation Scope" path="geolocation"]' );
			echo do_shortcode( '[murmurations_data title="Geographic Scope" path="geographic_scope"]' );
			echo do_shortcode( '[murmurations_data title="Contact Details" path="contact_details.email" second_path="contact_details.contact_form"]' );
			echo do_shortcode( '[murmurations_data title="Transaction Type" path="transaction_type"]' );
			?>
			<?php the_content(); ?>
            <div class="post-tags">
				<?php the_tags( '<span class="tag-links">' . esc_html__( 'Tagged ', 'hello-elementor' ), null, '</span>' ); ?>
            </div>
			<?php wp_link_pages(); ?>
        </div>

		<?php comments_template(); ?>

    </main>

<?php
endwhile;

get_footer();