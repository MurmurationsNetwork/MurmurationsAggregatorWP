<div class="murmurations-node <?= $data_classes ?>" id="<?= $data['url'] ?>">
  <table style="border:0;" class="murmurations-node-table">
    <tr>
      <td style="border:0; width:100px;">
  <div class="murmurations-node-image"><img src="<?= $data['logo'] ?>"></div>
</td>
<td style="border:0;">

 <div class="murmurations-node-content">
  <h3 class="murmurations-node-name"><?= $data['name'] ?></h3>
  <div class="murmurations-node-description"><?= wp_trim_words($data['description'],40,"...") ?></div>
  <div class="murmurations-node-coordinates">
    <?php

    $place_components = array();

    if($data['location']['locality']){
       $place_components[] = $data['location']['locality'];
    }

    if($data['location']['region']){
       $place_components[] = $data['location']['region'];
    }

    echo join(', ',$place_components);

    ?></div>
  <a class="murmurations-node-url" href="<?= $data['url'] ?>"><?= $data['url'] ?></a>
</div>
</td>
</tr>
</table>
</div>
