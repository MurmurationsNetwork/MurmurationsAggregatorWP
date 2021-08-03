<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_One
 * @since Twenty Twenty-One 1.0
 */

get_header();

/* Start the Loop */
while ( have_posts() ) :
	the_post();

  ?>
  <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

  	<header class="entry-header">
  		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
  	</header>

  	<div class="entry-content">
  		<?php
  		the_content();
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
