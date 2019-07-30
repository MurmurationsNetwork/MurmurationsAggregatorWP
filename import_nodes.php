<?php
/* File for importing nodes into the aggregator
Current functionality:
  Upload CSV to murmurations-aggregator/imports
  Load this file directly, with ?import_file=csv-filename.csv in the query string
  Nodes are geocoded if possible, and saved into the DB as custom post type (along with nodes from the network)

Future:
  Add admin page/tab for import, with settings
  Ability to import to JSON files, and notify the index
  Ability to do the above, but serve the files by WP API (to facilitate future permissions schemes)
  Validation of import data

*/




define('WP_USE_THEMES', false);
$base = dirname(dirname(__FILE__));
require($base.'../../../wp-load.php');

include_once(plugin_dir_path( __FILE__ ) . '../lazylog/lazylog.php');

if(!class_exists('Murmurations_Geocode')){
  require plugin_dir_path( __FILE__ ) . 'class-murmurations-geocode.php';
}

LazyLog::$settings['toFile'] = false;

$env = new Murmurations_Aggregator_WP();

$import_file = $_GET['import_file'];

$path = plugin_dir_path( __FILE__ ) . 'imports/';

echo"Importing from $path$import_file<br><br>";

if(!$import_file || ! file_exists($path.$import_file)){
  llog("Invalid import file");
}else{
  llog($path.$import_file,"Importing from");

  $import_data = file($path.$import_file);

  $headers = str_getcsv(array_shift($import_data));

  llog($headers,"Headers");

  foreach ($import_data as $node_str) {
    $node_indexed = str_getcsv($node_str);
    llog($node_indexed,"Node indexed");
    $node = array_combine($headers,$node_indexed);
    llog($node,"Importing node");

    if($node['location'] && !($node['lat'] && $node['lon'])){
      $geo = new Murmurations_Geocode($node['location']);
      if($geo->getCoordinates()){
        $node['lat'] = $geo->lat;
        $node['lon'] = $geo->lon;
      }else{
        llog("Couldn't get coordinates for location. Try a more specific address.");
      }
    }

    $env->save_node($node);
  }
}

LazyLog::flush();
