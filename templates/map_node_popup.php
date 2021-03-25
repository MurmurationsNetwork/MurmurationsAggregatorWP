<?php
$popup_str = "<b>".$data->murmurations['name']."</b><br><p>".$data->murmurations['tagline']."</p><div class=\"murmurations-node-mission\">".$data->murmurations['mission']."</div><div class=\"murmurations-node-org-types\">".$data->murmurations['nodeTypes']."</div><a class=\"murmurations-node-url\" href=\"".$data->murmurations['url']."\">".$data->murmurations['url']."</a>";

$popup_str = addslashes(str_replace(array("\r", "\n"),' ', $popup_str));

echo $popup_str;
