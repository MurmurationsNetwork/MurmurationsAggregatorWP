<?php
/**
 * The template for displaying singular post-types: posts, pages and user-defined custom post types.
 *
 * @package HelloElementor
 */

get_header();

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
	        echo do_shortcode( '[murmurations_data title="Description" path="description"]' );
	        echo do_shortcode( '[murmurations_data title="Primary URL" path="primary_url"]' );
	        echo do_shortcode( '[murmurations_data title="Tags" path="tags"]' );
	        echo do_shortcode( '[murmurations_data title="URLs" path="urls"]' );
	        echo do_shortcode( '[murmurations_data title="Relationships" path="relationships"]' );
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
