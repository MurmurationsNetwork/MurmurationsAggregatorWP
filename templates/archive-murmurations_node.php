<?php

get_header();

?>

<?php if ( have_posts() ) : ?>

	<header class="page-header">
    <div class="entry-header-inner">
    <h1 class="page-title"><?= post_type_archive_title( '', false ) ?></h1>
  </div>
	</header><!-- .page-header -->

	<?php while ( have_posts() ) : ?>
		<?php
    the_post();

    $post = get_post();
    $node = new Murmurations\Aggregator\Node($post);
    $data = $node->data;

    include( plugin_dir_path( __FILE__ ) . 'node_list_item.php' );

    ?>

	<?php endwhile; ?>
<?php
the_posts_pagination();
?>
<?php endif; ?>

<?php get_footer(); ?>
