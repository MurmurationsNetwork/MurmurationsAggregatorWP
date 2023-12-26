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
						echo 'This is from Offer Schema: ' . do_shortcode( '[murmurations_data path="description"]' );
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
