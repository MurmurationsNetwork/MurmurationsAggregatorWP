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
