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

    ?>

    <article id="<?= $post->ID ?>" class="post murmurations-node post-<?=$post->ID?> type-murmurations_node status-publish format-standard hentry">
      <header class="entry-header has-text-align-center">
    <?php the_title( '<h3 class="entry-title">', '</h3>' ); ?>
      </header>
      <div class="post-meta-wrapper">
    <?php if ( $data['url'] ) : ?>
      <div class="murmurations-node-url"><a href="<?php echo esc_url( $data['url'] ); ?>"><?php echo  esc_url( $data['url'] ); ?></a></div>
    <?php endif; ?>
      </div>
      <div class="entry-content">
        <?php
        the_excerpt();
        if ( Murmurations\Aggregator\Config::get('node_single') ) {
          if ( Murmurations\Aggregator\Config::get('node_single_url_field') ) {
            $href   = $data[ Murmurations\Aggregator\Config::get('node_single_url_field') ];
            $target = ' target="_blank"';
          } else {
            $href = $data['guid'];
          }
          ?><p><a href="<?php echo esc_url( $href ); ?>" <?php echo $target; ?>>read more</a></p>
          <?php
        }

      ?>
      </div>
      <?php if ( $data['location'] ) : ?>
      <div class="murmurations-node-location">
        <?php

        $place_components = array();

        if ( $data['location']['locality'] ) {
          $place_components[] = $data['location']['locality'];
        }

        if ( $data['location']['region'] ) {
          $place_components[] = $data['location']['region'];
        }

        echo esc_html( join( ', ', $place_components ) );

        ?>
      </div>
    <?php endif; ?>
  </article>
	<?php endwhile; ?>
<?php
the_posts_pagination();
?>
<?php endif; ?>

<?php get_footer(); ?>
