<?php
/**
 * The template for displaying singular post-types: posts, pages and user-defined custom post types.
 *
 * @package HelloElementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

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
			echo do_shortcode( '[murmurations_data title="Name" path="name"]' );
			echo do_shortcode( '[murmurations_data title="Nickname" path="nickname"]' );
			echo do_shortcode( '[murmurations_data title="Primary URL" path="primary_url"]' );
			echo do_shortcode( '[murmurations_data title="Tags" path="tags"]' );
			echo do_shortcode( '[murmurations_data title="Image" path="image"]' );
			echo do_shortcode( '[murmurations_data title="Knows Language" path="knows_language"]' );
			echo do_shortcode( '[murmurations_data title="Contact Details" path="contact_details"]' );
			echo do_shortcode( '[murmurations_data title="Telephone" path="telephone"]' );
			echo do_shortcode( '[murmurations_data title="Country Name" path="country_name"]' );
			echo do_shortcode( '[murmurations_data title="Country ISO 3166" path="country_iso_3166"]' );
			echo do_shortcode( '[murmurations_data title="Geolocation" path="geolocation"]' );
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
