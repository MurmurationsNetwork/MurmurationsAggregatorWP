<div class="murmurations-node <?php echo $data_classes; ?>" id="<?php echo $data['url']; ?>">
  <div class="murmurations-node-image"><img src="<?php echo $data['logo']; ?>" /></div>
  <div class="murmurations-node-content">
	<?php if ( $data['name'] ) : ?>
	<h3 class="murmurations-node-name"><?php echo $data['name']; ?></h3>
	<?php endif; ?>
	<?php if ( $data['url'] ) : ?>
	<div class="murmurations-node-url"><a href="<?php echo $data['url']; ?>"><?php echo $data['url']; ?></a></div>
	<?php endif; ?>
	<?php if ( $data['description'] ) : ?>
	<div class="murmurations-node-description"><?php echo wp_trim_words( $data['description'], 40, '...' ); ?>
		<?php
		if ( $this->config['node_single'] ) {
			if ( $this->config['node_single_url_field'] ) {
				$href   = $data[ $this->config['node_single_url_field'] ];
				$target = ' target="_blank"';
			} else {
				$href = $data['guid'];
			}
			?>
	  <a href="<?php echo $href; ?>" <?php echo $target; ?>>read more</a>
			<?php
		}
		?>
	</div>
	<?php endif; ?>
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

		echo join( ', ', $place_components );

		?>
	</div>
	<?php endif; ?>
	<?php

	$specific_outputs = array( 'logo', 'name', 'url', 'description', 'location' );

	foreach ( $data as $key => $value ) {
		if ( ! in_array( $key, $specific_outputs ) && in_array( $key, $this->config['list_fields'] ) ) {
			if ( is_array( $value ) ) {
				$value = join( ', ', $value );
			}
			if ( trim( $value ) ) {
				?>
		<div class="murmurations-list-field <?php echo $key; ?>">
		  <div class="label"><?php echo $this->schema['properties'][ $key ]['title']; ?></div>
		  <div class="value"><?php echo $value; ?></div>
		</div>
				<?php
			}
		}
	}
	?>
  </div>
</div>
