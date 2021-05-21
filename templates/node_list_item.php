<div class="murmurations-node <?= $data_classes ?>" id="<?= $data['url'] ?>">
  <div class="murmurations-node-image"><img src="<?= $data['logo'] ?>" /></div>
  <div class="murmurations-node-content">
    <?php if($data['name']): ?>
    <h3 class="murmurations-node-name"><?= $data['name'] ?></h3>
    <?php endif; ?>
    <?php if($data['url']): ?>
    <div class="murmurations-node-url"><a href="<?= $data['url'] ?>"><?= $data['url'] ?></a></div>
    <?php endif; ?>
    <?php if($data['description']): ?>
    <div class="murmurations-node-description"><?= wp_trim_words($data['description'],40,"...")?>
    <?php
    if($this->config['node_single']){
      if($this->config['node_single_base_url']){
        $href = $this->config['node_single_base_url'].$data['post_name'];
        $target =  ' target="_blank"';
      }else{
        $href = $data['guid'];
      }
      ?>
      <a href="<?= $href ?>" <?= $target ?>>read more</a>
      <?php
    }
    ?>
    </div>
    <?php endif; ?>
    <?php if($data['location']): ?>
    <div class="murmurations-node-location">
      <?php

      $place_components = array();

      if($data['location']['locality']){
         $place_components[] = $data['location']['locality'];
      }

      if($data['location']['region']){
         $place_components[] = $data['location']['region'];
      }

      echo join(', ',$place_components);

      ?>
    </div>
    <?php endif; ?>
    <?php

    $specific_outputs = array('logo','name','url','description','location');

    foreach ($data as $key => $value) {
      if(!in_array($key,$specific_outputs) && in_array($key,$this->config['list_fields'])){
        if(is_array($value)){
          $value = join(', ',$value);
        }
        if(trim($value)){
        ?>
        <div class="murmurations-list-field <?= $key ?>">
          <div class="label"><?= $this->schema['properties'][$key]['title'] ?></div>
          <div class="value"><?= $value ?></div>
        </div>
        <?php
        }
      }
    }
    ?>
  </div>
</div>
