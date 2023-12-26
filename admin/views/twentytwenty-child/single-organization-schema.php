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
						echo 'Name: ' . do_shortcode( '[murmurations_data path="name"]' ) . '<br>';
						echo 'Nickname: ' . do_shortcode( '[murmurations_data path="nickname"]' ) . '<br>';
						echo 'Description: ' . do_shortcode( '[murmurations_data path="description"]' ) . '<br>';
						echo 'Primary URL: ' . do_shortcode( '[murmurations_data path="primary_url"]' ) . '<br>';
						echo 'Tags: ' . do_shortcode( '[murmurations_data_array path="tags"]' ) . '<br>';
						echo 'URLs: ' . do_shortcode( '[murmurations_data_array path="urls.0"]' ) . '<br>';
						echo 'URLs: ' . do_shortcode( '[murmurations_data_array path="urls.1"]' ) . '<br>';
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
