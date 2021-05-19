<?php

function murmurations_index_update(){
  $ag = new Murmurations_Aggregator();
  $ag->updateNodes();
}

function murmurations_feed_update(){
   $ag = new Murmurations_Aggregator();
   $ag->updateFeeds();
}

?>
