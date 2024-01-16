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
