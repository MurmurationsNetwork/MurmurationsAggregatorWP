<?php
/**
 * The default template for displaying all single nodes
 *
 * @package Murmurations Aggregator
 */

 namespace Murmurations\Aggregator;

get_header();

/* Start the Loop */
while ( have_posts() ) :
	the_post();

	$data = Node::build_from_wp_post( get_post() );

  ?>
  <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

  	<header class="entry-header">
  		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
  	</header>

  	<div class="entry-content">
			<?php if ( $data['url'] ) : ?>
			<div class="murmurations-node-url"><a href="<?php echo esc_url( $data['url'] ); ?>"><?php echo esc_url( $data['url'] ); ?></a></div>
			<?php endif; ?>
			<?php if ( isset($data['image']) ) : ?>
			<div class="murmurations-node-image"><img src="<?php echo esc_url( $data['image'] ); ?>" onerror="this.style.display='none'"></div>
			<?php endif; ?>
  		<?php

  		the_content();

			$specific_outputs = array( 'logo', 'name', 'url', 'description', 'location', 'image', 'urls' );

	  	foreach ( $data as $key => $value ) {

				$field = Schema::get( $key );

				if ( ! in_array( $key, $specific_outputs ) && $field ) {

	  			if ( is_array( $value ) ) {
	  				$value = join( ', ', $value );
	  			}
	  			if ( trim( $value ) ) {
						if ( isset( $field['enumNames'] ) ) {
							$value = Utils::enum_label( $field['enum'], $field['enumNames'], $value );
						}
	  				?>
	  		<div class="murmurations-list-field <?php echo esc_attr( $key ); ?>">
	  		  <div class="label"><?php echo esc_html( $field['title'] ); ?></div>
	  		  <div class="value"><?php echo esc_html( $value ); ?></div>
	  		</div>
	  				<?php
	  			}
	  		}
			}

				?>
  	</div><!-- .entry-content -->


  </article><!-- #post-<?php the_ID(); ?> -->

  <?php

	// If comments are open or there is at least one comment, load up the comment template.
	if ( comments_open() || get_comments_number() ) {
		comments_template();
	}
endwhile; // End of the loop.

get_footer();
