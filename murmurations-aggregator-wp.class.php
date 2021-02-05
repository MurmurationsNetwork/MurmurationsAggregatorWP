<?php

/* Environment-specific functions for the Murmurations aggregator. Current intention, under experimentation, is to move these to the Murmurations_Environment class, which is then extended by the core class, to avoid current awkward inter-class call requirements*/

class Murmurations_Aggregator_WP{

  var $notices = array();
  var $template_directory = 'templates/';

  public function __construct(){
    $this->load_settings();
  }

  /* Methods are called from core class */

  /* Saves a node as WP post */
  public function save_node($node_data){

    llog($node_data,"Saving node data");


    if(!$node_data['name'] || !$node_data['url']){
      llog($node_data,"Missing or unacceptable required node data in save_node");
      $this->set_notice("Node is missing required data and won't be saved: <a href=\"$node_data[profile_url]\">$node_data[profile_url]</a>",'warning');
      return false;
    }

    $post_data = array();

    $post_data['post_title'] = $node_data['name'];
    $post_data['post_content'] = $node_data['name'];
    $post_data['post_excerpt'] = $node_data['tagline'];
    if(!$post_data['post_excerpt']){
      $post_data['post_excerpt'] = $node_data['name'];
    }
    $post_data['post_type'] = 'murmurations_node';
    $post_data['post_status'] = 'publish';

    // Check if node exists. If yes, update using existing post ID
    $existing_post = $this->load_node($node_data['profile_url']);

    if($existing_post){
      $post_data['ID'] = $existing_post->ID;
    }

    // Insert the post
    $result = wp_insert_post($post_data,true);

    if($result === false){
      llog($result,"Failed to insert post");
      return false;
    }else{
      llog($result,"Inserted post");
      $result === true ? $id = $post_data['ID'] : $id = $result;

      // And use the ID to update meta
      update_post_meta($id,'murmurations_node_url',$node_data['profile_url']);
      update_post_meta($id,'murmurations_node_data',$node_data);
      return $id;

    }
  }

  /* Load a murmurations_node post from WP */

  public function load_node($url){
    llog($url,"retrieving posts with URL");

    $args = array(
      'post_type' => 'murmurations_node',
       'meta_query' => array(
           array(
               'key' => 'murmurations_node_url',
               'value' => $url,
               'compare' => '=',
           )
        )
    );

    $posts = get_posts( $args );

    llog($posts,"Posts found in load_posts");

    if(count($posts) > 0){
      llog("Posts found");
      return $posts[0];
    }else{
      llog("No posts found");
      return false;
    }

  }

  /* Load multiple nodes from DB */
  public function load_nodes($limit = 1000){
    $args = array(
      'post_type'      => 'murmurations_node',
      'posts_per_page' => $limit,
    );

    $result = get_posts( $args );

    foreach ($result as $key => $post) {
      $result[$key]->murmurations = get_post_meta( $post->ID, 'murmurations_node_data', true );
    }

    return $result;
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


  /* Retrieve a setting */
  public function load_setting($setting){
    return $this->settings[$setting];
  }

  public function load_settings(){
    $this->settings = get_option('murmurations_aggregator_settings');
    return $this->settings;
  }

  /* Save a setting to the WP options table */
  public function save_settings(){
    llog($this->settings,"Settings in save_settings()");
    return update_option('murmurations_aggregator_settings',$this->settings);
  }

  public function save_setting($setting,$value){
    $this->settings[$setting] = $value;
    $this->save_settings();
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

  //TODO: Remove this from the WP class and use load_template instead
  public function format_node($node, $template = 'default'){

    $org_types_array = explode(', ',$node->murmurations['nodeTypes']);

    $data_classes = 'org-type-'.join(' org-type-',$org_types_array);

    if(file_exists(get_stylesheet_directory().'/murmurations-aggregator-templates/'.$template.'.php')){
      ob_start();
      include get_stylesheet_directory().'/murmurations-aggregator-templates/'.$template.'.php';
      $html = ob_get_clean();
    }else if(file_exists(dirname( __FILE__ ).'/templates/'.$template.'.php')){
      ob_start();
      include dirname( __FILE__ ).'/templates/' . $template . '.php';
      $html = ob_get_clean();
    }else{
      exit("Missing template file");
    }

    return $html;
  }

  // WP's enqueues don't accommodate integrity and crossorigin attributes without trickery, so we're using an action hook
  public function queue_leaflet_scripts(){
    llog("Queueing leaflet scripts");
    add_action( 'wp_head', array($this,'leaflet_scripts'));
  }

  public function leaflet_scripts(){
    llog("Outputting leaflet scripts");
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


  public function save_feed_item($item_data){

    if(!$item_data['url'] && $item_data['link']){
      $item_data['url'] = $item_data['link'];
    }

    $post_data = array();

    $post_data['post_title'] = $item_data['title'];
    $post_data['post_content'] = $item_data['content:encoded'];
    if(!$post_data['post_content']){
      $post_data['post_content'] = $item_data['title'];
    }

    // Get the images if possible
    preg_match('/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i',$item_data['description'], $image);

    if($image['src']){
      $item_data['image'] = $image['src'];
    }else if ($item_data['node_info']['logo']){
      $item_data['image'] = $item_data['node_info']['logo'];
    }

    $post_data['post_excerpt'] = substr(strip_tags($item_data['description']),0,300)."...";

    if(!$post_data['post_excerpt']){
      $post_data['post_excerpt'] = substr($post_data['post_content'],0,300)."...";
    }

    $post_data['post_type'] = 'murms_feed_item';
    $post_data['post_date'] = date('Y-m-d H:i:s', strtotime($item_data['pubDate']));

    $tags = $item_data['category'];

    $networks = explode(',',$item_data['node_info']['networks']);
    $node_types = explode(',',$item_data['node_info']['nodeTypes']);

    // Check if node exists. If yes, update using existing post ID
    $existing_post = $this->load_feed_item($item_data['url']);

    if($existing_post){
      $post_data['ID'] = $existing_post->ID;
    }else{
      $post_data['post_status'] = $this->load_setting('default_feed_item_status');
      echo llog($post_data['post_status'],"Saving with post status");
    }

    //echo llog($item_data,"RSS item data");

    //echo llog($post_data,"Feed item data before insert");

    // Insert the post
    $result = wp_insert_post($post_data,true);

    if($result === false){
      llog($result,"Failed to insert feed item post");
    }else{
      llog($result,"Inserted feed item post");
      $result === true ? $id = $post_data['ID'] : $id = $result;

      // Add terms directly
      $tresult = wp_set_object_terms($id, $tags, 'murms_feed_item_tag');
      $tresult1 = wp_set_object_terms($id, $node_types, 'murms_feed_item_node_type');
      $tresult2 = wp_set_object_terms($id, $networks, 'murms_feed_item_network');

      // And use the ID to update meta
      update_post_meta($id,'murmurations_feed_item_url',$item_data['url']);
      update_post_meta($id,'murmurations_feed_item_data',$item_data);

    }


  }

  public function delete_all_feed_items(){

    $args = array(
      'post_type' => 'murms_feed_item',
      'posts_per_page' => -1
    );

    $posts = get_posts( $args );

    foreach ($posts as $post) {
      wp_delete_post($post->ID,true);
    }

  }

  /* Load a murmurations_feed_item post from WP */

  public function load_feed_item($url){

    $args = array(
      'post_type' => 'murms_feed_item',
      'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash'),

      'meta_query' => array(
           array(
               'key' => 'murmurations_feed_item_url',
               'value' => $url,
               'compare' => '=',
           )
       )
    );

    $posts = get_posts( $args );

    if(count($posts) > 0){
      return $posts[0];
    }else{
      return false;
    }
  }
}
?>
