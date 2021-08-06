<div class="murmurations-node <?php echo $data_classes; ?>" id="<?php echo $node->murmurations['url']; ?>">
  <table style="border:0;" class="murmurations-node-table">
	<tr>
	  <td style="border:0; width:100px;">
  <div class="murmurations-node-image"><img src="<?php echo $node->murmurations['logo']; ?>"></div>
</td>
<td style="border:0;">

 <div class="murmurations-node-content">
  <h3 class="murmurations-node-name"><?php echo $node->murmurations['name']; ?></h3>
  <div class="murmurations-node-tagline"><?php echo $node->murmurations['tagline']; ?></div>
  <div class="murmurations-node-mission"><?php echo $node->murmurations['mission']; ?></div>
  <div class="murmurations-node-description"><?php echo wp_trim_words( $node->murmurations['description'], 40, '...' ); ?></div>
  <div class="murmurations-node-org-types"><?php echo $node->murmurations['nodeTypes']; ?></div>
  <div class="murmurations-node-coordinates">
	<?php

	$place_components = array();

	if ( $node->murmurations['location']['locality'] ) {
		$place_components[] = $node->murmurations['location']['locality'];
	}

	if ( $node->murmurations['location']['region'] ) {
		$place_components[] = $node->murmurations['location']['region'];
	}

	echo join( ', ', $place_components );

	?>
	</div>
  <a class="murmurations-node-url" href="<?php echo $node->murmurations['url']; ?>"><?php echo $node->murmurations['url']; ?></a>
</div>
</td>
</tr>
</table>
</div>
