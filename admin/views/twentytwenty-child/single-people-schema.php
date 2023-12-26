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
						echo 'Primary URL: ' . do_shortcode( '[murmurations_data path="primary_url"]' ) . '<br>';
						echo 'Tags: ' . do_shortcode( '[murmurations_data path="tags"]' ) . '<br>';
						echo 'Image: ' . do_shortcode( '[murmurations_data path="image"]' ) . '<br>';
						echo 'Knows Language: ' . do_shortcode( '[murmurations_data path="knows_language"]' ) . '<br>';
						echo 'Contact Details: ' . do_shortcode( '[murmurations_data path="contact_details"]' ) . '<br>';
						echo 'Telephone: ' . do_shortcode( '[murmurations_data path="telephone"]' ) . '<br>';
						echo 'Country Name: ' . do_shortcode( '[murmurations_data path="country_name"]' ) . '<br>';
						echo 'Country ISO 3166: ' . do_shortcode( '[murmurations_data path="country_iso_3166"]' ) . '<br>';
						echo 'Geolocation: ' . do_shortcode( '[murmurations_data path="geolocation"]' ) . '<br>';
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
