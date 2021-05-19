<?php
$popup_str = "<b>".$data['name']."</b><br><p>". wp_trim_words($data['description'],60,"...")."</p>";

$popup_str = addslashes(str_replace(array("\r", "\n"),' ', $popup_str));

echo $popup_str;
