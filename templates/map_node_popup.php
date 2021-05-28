<?php
$popup_str = '<div class="murmurations-map-popup">';
$popup_str .= '<div><b>'.$data['name']."</b></div>";
if($data['url']){
  $popup_str .= '<div class="url"><a href="'.$data['url'].'">'.$data['url'].'</a></div>';
}
if($data['description']){
  $popup_str .= '<div class="description">'.wp_trim_words($data['description'],60,"...");
  if($this->config['node_single']){
    if($this->config['node_single_url_field']){
      $href = $data[$this->config['node_single_url_field']];
      $target =  ' target="_blank"';
    }else{
      $href = $data['guid'];
    }
    $popup_str .= ' <a href="'.$href.'" '.$target.'>read more</a>';

  }
  $popup_str .= '</div>';
}
$popup_str .= '</div>';

$popup_str = addslashes(str_replace(array("\r", "\n"),' ', $popup_str));

echo $popup_str;
