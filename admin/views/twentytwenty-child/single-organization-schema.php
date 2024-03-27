<?php

get_header();
?>

    <main id="site-content">

		<?php

		if ( have_posts() ) {

			while ( have_posts() ) {
				the_post();

				get_template_part( 'template-parts/content', get_post_type() );

				?>

                <div class="entry-content">
                    <p>
						<?php
						echo do_shortcode( '[murmurations_image path="image"]' );
						echo do_shortcode( '[murmurations_data title="Name" path="name"]' );
						echo do_shortcode( '[murmurations_data title="Nickname" path="nickname"]' );
						echo do_shortcode( '[murmurations_data title="Description" path="description"]' );
						echo do_shortcode( '[murmurations_data title="Primary URL" path="primary_url"]' );
						echo do_shortcode( '[murmurations_data title="Tags" path="tags"]' );
						echo do_shortcode( '[murmurations_data title="URLs" path="urls"]' );
						echo do_shortcode( '[murmurations_data title="Relationships" path="relationships"]' );
						?>
                    </p>
                </div>
				<?php
			}
		}

		?>

    </main><!-- #site-content -->

<?php get_template_part( 'template-parts/footer-menus-widgets' ); ?>

<?php
get_footer();
