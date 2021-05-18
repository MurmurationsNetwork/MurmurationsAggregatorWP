<?php

class Murmurations_Aggregator_WP{

  public $notices = array();
  public $template_directory = 'templates/';

  /*

  public $default_settings = array(
    'map_origin' => '49.505, -29.09',
    'map_scale' => '1.7',
    // API key for Mapbox, tile provider for Leaflet. https://www.mapbox.com/studio/account/tokens/
    'mapbox_token' => 'pk.eyJ1IjoibXVybXVyYXRpb25zIiwiYSI6ImNqeGN2MTIxYTAwMWQzdnBhODlmOHRyeXEifQ.KkzeMmUS2suuPI_n3l7jAA',
  );

  */


  public function __construct($config){

    $default_config = array(
      'plugin_name' => 'Murmurations Aggregator',
      'node_name' => 'Murmurations Node',
      'node_slug' => 'murmurations_node',
      'feed_storage_path' => plugin_dir_path(__FILE__).'feeds/feeds.json',
      'schema_file' => plugin_dir_path(__FILE__).'schema.json',
      'field_map_file' plugin_dir_path(__FILE__).'field_map.json',
    );

    $this->config = wp_parse_args($config, $default_config);

    $this->load_includes();
    $this->load_settings();
    $this->load_schema();
    $this->load_field_map();
    $this->register_hooks();
  }

  public function get_setting($setting){
    return $this->settings[$setting];
  }

  public function load_settings(){
    $this->settings = get_option('murmurations_aggregator_settings');
    return $this->settings;
  }

  /* Save a setting to the WP options table */
  public function save_settings(){
    return update_option('murmurations_aggregator_settings',$this->settings);
  }

  public function save_setting($setting,$value){
    $this->settings[$setting] = $value;
    $this->save_settings();
  }

  public static function load_schema(){
    $schema_file = plugin_dir_path(__DIR__) .'schema.json';
    $schema_json = file_get_contents($schema_file);
    $this->schema = json_decode($schema_json,true);
  }

  public static function load_field_map(){
    $map_file = plugin_dir_path(__DIR__) .'field_map.json';
    $map_json = file_get_contents($map_file);
    $this->field_map = json_decode($map_json,true);
  }

  /* Activate the plugin */
  public function activate(){

    $fields = json_decode(file_get_contents(dirname( __FILE__ ).'/admin_fields.json'),true);

    $default_settings = array();

    foreach ($fields as $name => $field) {
      if($field['default']){
        $default_settings[$name] = $field['default'];
      }
    }

    /*
    if($_SERVER['host'] == 'localhost'){
      $default_settings['index_url'] = 'http://localhost/projects/murmurations/murmurations-index/murmurations-index.php';
    }
    */

    $this->settings = $default_settings;

    $this->save_settings();

  }

  /* Deactivate the plugin */
  public function deactivate(){
    //
  }



  /* Load an overridable template file */
  public function load_template($template,$data){
    if(file_exists(get_stylesheet_directory().'/murmurations-aggregator-templates/'.$template.'.php')){
      ob_start();
      include get_stylesheet_directory().'/murmurations-aggregator-templates/'.$template.'.php';
      $html = ob_get_clean();
    }else if(file_exists(dirname( __FILE__ ).'/templates/'.$template.'.php')){
      ob_start();
      include dirname( __FILE__ ).'/templates/' . $template . '.php';
      $html = ob_get_clean();
    }else{
      exit("Missing template file: ".$template);
    }
    return $html;
  }


  // WP's enqueues don't accommodate integrity and crossorigin attributes without trickery, so we're using an action hook
  public function queue_leaflet_scripts(){
    add_action( 'wp_head', array($this,'leaflet_scripts'));
  }

  public function leaflet_scripts(){
    ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.5.1/dist/leaflet.css"
  integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ=="
  crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.5.1/dist/leaflet.js"
  integrity="sha512-GffPMF3RvMeYyc1LWMHtK8EbPv0iNZ8/oTtHPx9/cc2ILxQ+u905qIwdpULaqDkyBKgOaB57QTMg7ztg8Jm2Og=="
  crossorigin=""></script>
  <?php
  }

  public function show_admin_settings_page(){

      if($_POST['action']){
        check_admin_referer( 'murmurations_ag_actions_form');
        if($_POST['action'] == 'update_murms_feed_items'){
          $this->updateFeeds();
        }
        if($_POST['action'] == 'update_nodes'){
          $this->updateNodes();
        }
        if($_POST['action'] == 'delete_all_nodes'){
          $this->delete_all_nodes();
        }
      }

     echo "<h1>Murmurations Aggregator</h1>";
     ?>
     <form method="POST">
     <?php
     wp_nonce_field( 'murmurations_ag_actions_form' );
     ?>
     <button type="submit" name="action" class="murms-update murms-has-icon" value="update_nodes"><i class="murms-icon murms-icon-update"></i>Update nodes</button>
     <button type="submit" name="action" class="murms-update murms-has-icon" value="update_murms_feed_items"><i class="murms-icon murms-icon-update"></i>Update feeds</button>

     <button type="submit" name="action" class="murms-delete murms-has-icon" value="delete_all_nodes"><i class="murms-icon murms-icon-delete"></i>Delete all stored nodes</button>

   </form>
   <?php


    // Process form data
    if (isset($_POST['murmurations_ag'])) {
      $this->process_admin_form();
    }

    echo $this->show_notices();

    /* TODO: Separate sections into their own JS-toggled tabs

    $admin_tabs = array(
      'network-settings' => 'Network Settings',
      'feeds' => 'Feeds'
    );

    $tab = $_GET['tab'];

    if($tab === null) $tab = 'network-settings';


    echo '<nav class="nav-tab-wrapper">';
    foreach ($admin_tabs as $key => $name) {
      echo "<a href=\"?page=murmurations-aggregator-settings&tab=$key\" class=\"nav-tab ";
      if($tab === $key) echo 'nav-tab-active';
      echo "\">$name</a>";
    }

    echo "</nav>";

    */

    $this->show_admin_form($murm_post_data);

  }

  public function load_admin_fields(){
    return json_decode(file_get_contents(dirname( __FILE__ ).'/admin_fields.json'),true);
  }


  public function show_admin_form($post_data = false){
    $current = $this->settings;

    $fields = json_decode(file_get_contents(dirname( __FILE__ ).'/admin_fields.json'),true);

    $field_groups = array();

    // Reorganize into sections
    foreach ($fields as $name => $field_info) {
      $field_groups[$field_info['group']][$name] = $field_info;
    }

    ?>
    <form method="POST">
    <?php
    wp_nonce_field( 'murmurations_ag_admin_form' );

    foreach ($field_groups as $group => $fields) {
      $name = ucfirst(str_replace('_',' ',$group));
      ?>
      <div id="murms-admin-form-section-<?php echo $group ?>" class="murms-admin-form-section">
        <h2><?php echo $name ?></h2>
      <?php

      foreach ($fields as $key => $f) {
        $f['name'] = "murmurations_ag[$key]";
        $f['current_value'] = $current[$key];

        ?>
        <div class="murmurations-ag-admin-field">
          <label for="<?= $f['name'] ?>"><?= $f['title'] ?></label>
          <?php
          echo $this->admin_field($f);
          ?>
        </div>

        <?php
      }

      echo "</div>";

    }

    ?>
    <input type="submit" value="Save" class="button button-primary button-large">
</form>
<?php

  }

  public function admin_field($f){

    // This is very rudimentary now. Possibly should be replaced with a field class
    if($f['inputAs'] == 'text'){

      $out = '<input type="text" class="" name="'.$f['name'].'" id="'.$f['name'].'" value="'.$f['current_value'].'" />';

    }else if($f['inputAs'] == 'checkbox'){

      if ($f['current_value'] == 'true'){
        $checked = 'checked';
      }else{
        $checked = '';
      }
      $out = '<input type="checkbox" class="checkbox" name="'.$f['name'].'" id="'.$f['name'].'" value="true" '.$checked.'/>';

    }else if($f['inputAs'] == 'select'){
       $options = $f['options'];
       $out = '<select name="'.$f['name'].'" id="'.$f['name'].'">';
       $out .= $this->show_select_options($options,$f['current_value']);
       $out .= '</select>';


    }else if($f['inputAs'] == 'template_selector'){
      // This should be updated to find templates in the css directory
      // (It's overridable as is, but only by files of the same name)

      $files = array_diff(scandir(dirname( __FILE__ ).'/'.$this->template_directory), array('..', '.'));

      $options = array();

      foreach ($files as $key => $fn) {
        if(substr($fn,-4) == ".php"){
          $name = substr($fn,0,-4);
          $options[$name] = $name;
        }
      }

      $out = '<select name="'.$f['name'].'" id="'.$f['name'].'">';
      $out .= $this->show_select_options($options,$f['current_value']);
      $out .= '</select>';

    }else if($f['inputAs'] == 'multiple_array'){

        $filters = $f['current_value'];

        $out = '<div class="murmurations_ag_filter_field_set">';
        $out .= '<table><tr><th>Field</th><th>Match type</th><th>Value</th></tr>';
        $filter_count = 0;

        if(is_array($filters)){
          foreach ($filters as $key => $value) {
            $out .= $this->show_filter_field($filter_count,$value);
            $filter_count++;
          }
        }

        while($filter_count < 5){
          $out .= $this->show_filter_field($filter_count);
          $filter_count++;
        }

        $out .= '</table></div>';
    }
    return $out;
  }

  public function show_filter_field($id,$current_value = false){

    $keys = array('subject','predicate','object');

    if(!$current_value){
      $current_value = array('','','');
    }

    $current_value = array_combine($keys, $current_value);

    //TODO: This needs to come from the appropriate murmurations schema, or else be a non constrained field
    $subject_options = array(
      '' => "",
      'nodeTypes' => "Node types",
      'url' => "URL",
      'mission' => "Mission",
      'name' => "Name",
      'networks' => "Networks"
    );


    $match_options = array(
      '' => "",
      'includes' => "Includes",
      'equals' => "Equals",
      'isGreaterThan' => "Is greater than",
      'isIn' => "Is in",
    );


    $out  = '<tr><td><select name="filters['.$id.'][subject]">';
    $out .= $this->show_select_options($subject_options,$current_value['subject']);
    $out .= '</select></td><td>';
    $out .= '<select name="filters['.$id.'][predicate]">';
    $out .= $this->show_select_options($match_options,$current_value['predicate']);
    $out .= '</select></td><td>';
    $out .= '<input type="text" class="" name="filters['.$id.'][object]" value="'.$current_value['object'].'" />';
    $out .= '</select></td></tr>';

    return $out;
  }

  public function show_select_options($options, $current = false){
    $out = "";
    foreach ($options as $key => $value) {
      if($current && $key == $current) $selected = "selected";
      $out .= '<option '.$selected.' value="'.$key.'">'.$value.'</option>'."\n";
      $selected = "";
    }
    return $out;
  }

  /* Process node data saved from the admin page */
  public function process_admin_form(){

    $fields = $this->load_admin_fields();

    $murm_post_data = $_POST['murmurations_ag'];

    // Check the WP nonce
    check_admin_referer( 'murmurations_ag_admin_form');

    // Catch the filter fields and process

    if(is_array($_POST['filters'])){
      foreach ($_POST['filters'] as $key => $filter) {
        if($filter['subject'] && $filter['predicate'] &&  $filter['object']){
          $murm_post_data['filters'][] = array(
            $filter['subject'],
            $filter['predicate'],
            $filter['object']
          );
        }
      }
    }


    foreach ($fields as $key => $f) {
       $this->settings[$key] = $murm_post_data[$key];
    }

    $this->save_settings();

    $this->set_notice("Data saved",'success');

  }

  public function set_notice($message,$type = 'notice'){

    $this->notices[] = array('message'=>$message,'type'=>$type);
    $_SESSION['murmurations_notices'] = $this->notices;

  }

  function get_notices(){
    $notices = array();
    if(count($this->notices) > 0){
      $notices = $this->notices;
    }else if(isset($_SESSION['murmurations_notices'])){
      $notices = $_SESSION['murmurations_notices'];
    }
    unset($_SESSION['murmurations_notices']);
    return $notices;
  }

  function show_notices(){
    $notices = $this->get_notices();
    foreach ($notices as $notice) {
      ?>
      <div class="notice notice-<?php echo $notice['type']; ?>">
					<p><?php echo $notice['message']; ?></p>
			</div>

      <?php
    }
  }

  /* Generate the HTML for directory output */

  public function format_nodes($nodes){
    //llog($nodes,"Nodes");
    $html = '<div id="murmurations-directory">';
    foreach ($nodes as $key => $node) {
      $html .= $this->format_node($node);
    }
    $html .= "</div>";
    echo $html;
  }


  public function load_nodes($args = null){

    $deafult_args = array(
      'post_type'      => 'murmurations_node',
      'posts_per_page' => -1,
    );

    $args = wp_parse_args($args,$deafult_args);

    $posts = get_posts( $args );

    if(count($posts) > 0){
      foreach ($posts as $key => $post) {
        $this->nodes[$post->ID] = new Murmurations_Node();

        $this->nodes[$post->ID]->buildFromWPPost($post);

      }
    }else{
      llog("No node posts found in load_nodes using args: ".print_r($args,true));
    }
  }

  public function delete_all_nodes(){
    $nodes = get_posts( array('post_type'=>'murmurations_node','numberposts'=> -1) );
    $count = 0;
    foreach ($nodes as $node) {
      $result = wp_delete_post($node->ID, true);
      if($result){
        $count++;
      }
    }
    $this->set_notice("$count nodes deleted");
    return $count;
  }



  /* Update all locally-stored node data from the index and nodes, adding new matching nodes and updating existing nodes */
  public function updateNodes(){

    $settings = $this->settings;

    $filters = $settings['filters'];

    if(is_array($filters)){
      foreach ($filters as $key => $condition) {
        if(in_array($condition[0],$this->index_fields)){
          $index_filters[] = $condition;
        }
      }
    }

    $update_since = $settings['update_time'];

    if($settings['ignore_date'] != 'true'){
      $index_filters[] = array('updated','isGreaterThan',$update_since);
    }

    $query = array(
      'action' => 'get_nodes',
      'conditions' => $index_filters
    );

    foreach ($settings['indices'] as $index){

      $url = $index['url'];

      if($index['api_key']){
        $options['api_key'] = $index['api_key'];
      }

      $queried_nodes = Murmurations_API::getIndexJson($url,$query,$options);

      if($index_nodes){
        $index_nodes = array_merge($index_nodes,$queried_nodes);
      }else{
        $index_nodes = $queried_nodes;
      }

    }

    if(!$index_nodes){
      $this->setNotice("Could not connect to the index","error");
      return false;
      /* TODO: Even if the index is out, could still query from stored nodes */
    }

    $failed_nodes = array();
    $fetched_nodes = array();
    $matched_nodes = array();
    $saved_nodes = array();

    $results = array(
      'nodes_from_index' => array(),
      'failed_nodes' => array(),
      'fetched_nodes' => array(),
      'matched_nodes' => array(),
      'saved_nodes' => array()
    );

    // Query the nodes to collect the data
    foreach ($index_nodes as $key => $data) {

      $url = $data['profile_url'];

      $results['nodes_from_index'][] = $url;

      // Get the JSON from the node
      $node_data = Murmurations_API::getNodeJson($url);

      if(!$node_data){
        $results['failed_nodes'][] = $url;
      }else{

        $results['fetched_nodes'][] = $url;

        $node = new Murmurations_Node();

        $build_result = $node->buildFromJson($node_data);

        if(!$build_result){
          $this->error($node->getErrors());
        }

        $matched = $node->checkFilters($filters);

        if($matched == true){
          $results['matched_nodes'][] = $url;

          $node_data_ar['profile_url'] = $url;

          $result = $node->save();

          if($result){
            $results['saved_nodes'][] = $url;
          }else{
            $this->setNotice("Failed to save node: ".$url,"error");
          }

        }else{
          if($settings['unmatching_local_nodes_action'] == 'delete'){
            $node->delete();
          } else {
            $node->deactivate();
          }
        }
      }
    }


    $message = "Nodes updated. ".count($results['nodes_from_index'])." nodes fetched from index. ".count($results['failed_nodes'])." failed. ".count($results['fetched_nodes'])." nodes returned results. ".count($results['matched_nodes'])." nodes matched filters. ".count($results['saved_nodes'])." nodes saved. ";

    if(count($results['saved_nodes']) > 0){
      $class = 'success';
    }else{
      $class = 'notice';
    }

    $this->setNotice($message,$class);

    $this->save_setting('update_time',time());

  }


  public function showDirectory(){
    $nodes = $this->load_nodes();

    return $this->format_nodes($nodes);
  }

  public function showMap(){
    $nodes = $this->load_nodes();

    /* Because of the cross-origin stuff, these don't fit WP's queue paradigm. In future, we should use this method, but for now loading scripts as HTML in the head via env
    $this->env->add_css(array(
      'href'=>"https://unpkg.com/leaflet@1.5.1/dist/leaflet.css",
      'integrity' => "sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ==",
      'crossorigin' => "");

    $this->env->add_script(array(
        'href'=>"https://unpkg.com/leaflet@1.5.1/dist/leaflet.js",
        'integrity' => "sha512-GffPMF3RvMeYyc1LWMHtK8EbPv0iNZ8/oTtHPx9/cc2ILxQ+u905qIwdpULaqDkyBKgOaB57QTMg7ztg8Jm2Og==",
        'crossorigin' => "");
    */

    //This API recently changed (https://docs.mapbox.com/help/troubleshooting/migrate-legacy-static-tiles-api)

    $html = $this->leaflet_scripts();

    $map_origin = $this->settings['map_origin'];
    $map_scale = $this->settings['map_scale'];

    $html .= '<div id="murmurations-map" class="murmurations-map"></div>'."\n";
    $html .= '<script type="text/javascript">'."\n";
    $html .= "var murmurations_map = L.map('murmurations-map').setView([".$map_origin."], $map_scale);\n";

    $html .= "L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token={accessToken}', {
    attribution: 'Map data &copy; <a href=\"https://www.openstreetmap.org/\">OpenStreetMap</a> contributors, <a href=\"https://creativecommons.org/licenses/by-sa/2.0/\">CC-BY-SA</a>, Imagery © <a href=\"https://www.mapbox.com/\">Mapbox</a>',
    tileSize: 512,
    maxZoom: 18,
    zoomOffset: -1,
    id: 'mapbox/streets-v11',
    accessToken: '".$this->settings['mapbox_token']."'
}).addTo(murmurations_map);\n";

    foreach ($nodes as $key => $node) {

      //llog($node);

      if(is_numeric($node->murmurations['geolocation']['lat']) && is_numeric($node->murmurations['geolocation']['lon'])){

          $popup = trim($this->env->load_template('map_node_popup',$node));

          $lat = $node->murmurations['geolocation']['lat'];
          $lon = $node->murmurations['geolocation']['lon'];

          $html .= "var marker = L.marker([".$lat.", ".$lon."]).addTo(murmurations_map);\n";
          $html .= "marker.bindPopup(\"$popup\");\n";

       }
    }

    $html .= "</script>\n";

    return $html;
  }

  public function load_includes(){
    $include_path = plugin_dir_url( __FILE__ ) . 'includes/';
    require_once $include_path.'murmurations-api.class.php';
    require_once $include_path.'murmurations-node.class.php';
    require_once $include_path.'murmurations-geocode.class.php';
    //require_once $include_path.'murmurations-feeds.class.php';
  }

  public function register_hooks(){

    add_action('init', array($this, 'register_cpts_and_taxes'));

    add_shortcode('murmurations-directory', array($this, 'showDirectory'));
    add_shortcode('murmurations-map', array($this, 'showMap'));
    add_shortcode('murmurations-feeds', array($this, 'showFeeds'));

    add_action( 'admin_menu', array($this, 'add_settings_page') );

    register_activation_hook( __FILE__, array($this, 'activate') );
    register_deactivation_hook( __FILE__, array($this, 'deactivate') );

    wp_enqueue_style('murmurations-agg-css', plugin_dir_url( __FILE__ ) . 'css/murmurations-aggregator.css');

  }

  public function add_settings_page() {

    $args = array(
      'page_title' => 'Murmurations Aggregator Settings',
      'menu_title' => 'Murmurations Aggregator',
      'capability' => 'manage_options',
      'menu_slug' => 'murmurations-aggregator-settings',
      'function' => array($this,'show_admin_settings_page'),
    );

    add_menu_page($args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function']);

  }

  public function register_cpts_and_taxes(){


    register_post_type('murmurations_node',
       array(
           'labels'      => array(
               'name'          => __('Nodes'),
               'singular_name' => __('Node'),
           ),
           'public'      => true,
           'has_archive' => true,
           'menu_icon'   => 'dashicons-rest-api',
           'rewrite'     => array( 'slug' => 'nodes' ), //TODO: This should be a setting, so aggregator sites can set the slug prefix. This also means we want to move this into the environment class, so we can access stuff from there (but there's the small matter of how to get that instantiated from the main file and call this without having to pass environment-specific information in the core class)
       )
    );


    register_post_type('murms_feed_item',
       array(
           'labels'      => array(
               'name'          => __('Murmurations Feed Items'),
               'singular_name' => __('Murmurations Feed Item'),
           ),
           'public'      => true,
           'has_archive' => true,
           'menu_icon'   => 'dashicons-rss',
           'rewrite'     => array( 'slug' => 'murmurations-feed-item' )
       )
    );

    register_taxonomy('murms_feed_item_tag','murms_feed_item');


    register_taxonomy(
      'murms_feed_item_node_type',
      'murms_feed_item',
      array(
        'labels'  => array(
          'name'  => __( 'Types' ),
          'singular_name' => __( 'Type' ),
        ),
        'show_admin_column' => true
      )
    );
    register_taxonomy(
      'murms_feed_item_network',
      'murms_feed_item',
      array(
        'labels'  => array(
          'name'  => __( 'Networks' ),
          'singular_name' => __( 'Network' ),
        ),
        'show_admin_column' => true
      )
    );

  }


  /*
  public function updateFeeds(){

    $this->env->delete_all_feed_items();

    $feed_items = array();
    //$since_time = $this->env->load_setting('feed_update_time');

    $max_feed_items = $this->env->settings['max_feed_items'];
    $max_feed_items_per_node = $this->env->settings['max_feed_items_per_node'];

    // Get the locally stored nodes
    $nodes = $this->env->load_nodes();

    $results = array(
      'nodes_with_feeds' => 0,
      'feed_items_fetched' => 0,
      'feed_items_saved' => 0
    );

    foreach ($nodes as $node) {

      //echo llog($node,"Node for fetching feed");

      if($node->murmurations['feed']){
        $feed_url = $node->murmurations['feed'];

        $feed = $this->feedRequest($feed_url);

        // For some reason this comes with an *xml key that misbehaves...
        $feed = array_shift($feed);

        if(is_array($feed)){
          $node_item_count = 0;

          $results['nodes_with_feeds']++;

          //echo llog($feed);

          //exit();

          /* RSS includes multiple <item> elements. The RSS parser adds a single ['item'],
          with numerically indexed elements for each item from the RSS. But, if there is only one item in the feed, it doesn't do this, and ['item'] is an array of item properties, not items

          if(!$feed['item'][0]){
            $temp = $feed['item'];
            unset($feed['item']);
            $feed['item'][0] = $temp;
          }

          foreach ($feed['item'] as $item) {
            if(is_array($item)){
              $node_item_count++;
              // Add the info about the node to each item
              $item['node_info'] = $node->murmurations;
              $feed_items[] = $item;
              if($node_item_count == $max_feed_items_per_node) break;

            }else{
              echo llog($feed,"Strange non-array feed item.");
            }
          }
        }else{
          $results['broken_feeds']++;
          $this->setNotice("This feed could not be parsed: $feed_url",'warning');
        }
      }
    }

    // Sort reverse chronologically
    usort($feed_items, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });

    $results['feed_items_fetched'] = count($feed_items);

    $count = 0;

    foreach ($feed_items as $key => $item) {

      $result = $this->env->save_feed_item($item);

      $results['feed_items_saved']++;
      if($results['feed_items_saved'] == $max_feed_items){
        break;
      }
    }

    $this->setNotice("Feeds updated. ".$results['feed_items_fetched']." feed items fetched from ".$results['nodes_with_feeds']." nodes. ".$results['feed_items_saved']." feed items saved.",'success');

  }

*/


}
?>
