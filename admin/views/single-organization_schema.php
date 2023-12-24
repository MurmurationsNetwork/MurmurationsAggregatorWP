<?php get_header(); ?>

<main id="main" class="site-main" role="main">
	<?php
	while ( have_posts() ) : the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="entry-header">
				<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				<div class="entry-meta">
					<span class="post-date"><?php the_date(); ?></span>
					<span class="post-author"><?php the_author(); ?></span>
				</div>
			</header>

			<div class="entry-content">
				<?php
				the_content();

				wp_link_pages( array(
					'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'textdomain' ),
					'after'  => '</div>',
				) );
				?>
				<?php
				echo  'This is from MURMUR plugin: ' . do_shortcode('[murmurations_data path="description"]');
				?>
			</div><!-- .entry-content -->

			<footer class="entry-footer">
				<?php edit_post_link( esc_html__( 'Edit', 'textdomain' ), '<span class="edit-link">', '</span>' ); ?>
			</footer><!-- .entry-footer -->

		</article><!-- #post-## -->

		<?php
		// If comments are open or there is at least one comment, load the comments template.
		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}

	endwhile;
	?>

</main><!-- #main -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
