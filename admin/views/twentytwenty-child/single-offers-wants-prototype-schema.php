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
	                    echo 'Exchange Type: ' . do_shortcode( '[murmurations_data path="exchange_type"]' ) . '<br>';
	                    echo 'Item Type: ' . do_shortcode( '[murmurations_data path="item_type"]' ) . '<br>';
	                    echo 'Tags: ' . do_shortcode( '[murmurations_data path="tags"]' ) . '<br>';
	                    echo 'Title: ' . do_shortcode( '[murmurations_data path="title"]' ) . '<br>';
	                    echo 'Description: ' . do_shortcode( '[murmurations_data path="description"]' ) . '<br>';
	                    echo 'Geolocation: ' . do_shortcode( '[murmurations_data path="geolocation"]' ) . '<br>';
	                    echo 'Geographic Scope: ' . do_shortcode( '[murmurations_data path="geographic_scope"]' ) . '<br>';
	                    echo 'Contact Details: ' . do_shortcode( '[murmurations_data path="contact_details.contact_form"]' ) . '<br>';
	                    echo 'Transaction Type: ' . do_shortcode( '[murmurations_data path="transaction_type"]' ) . '<br>';
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
