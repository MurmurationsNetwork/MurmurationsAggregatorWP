<?php
namespace Murmurations\Aggregator;

class Feeds {

  public $wpagg;

  public static function init(){

    require_once MURMAG_ROOT_PATH .'libraries/Feed.php';

    add_action( 'murmurations_feed_update', array(self, 'update_feeds' ) );

    add_shortcode(Config::get('plugin_slug').'-feeds', array(self, 'show_feeds'));

    register_post_type('murms_feed_item',
       array(
           'labels'      => array(
               'name'          => Config::get('plugin_name').' Feed Items',
               'singular_name' => Config::get('plugin_name').' Feed Item',
           ),
           'public'      => true,
           'has_archive' => true,
           'menu_icon'   => 'dashicons-rss',
           //'show_in_menu' => 'murmurations-aggregator-settings',
           'show_in_menu' => true,
           'menu_position' => 21,
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

  public static function save_feed_item($item_data){

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

    // Check if node exists. If yes, update using existing post ID
    $existing_post = self::load_feed_item($item_data['url']);

    if($existing_post){
      $post_data['ID'] = $existing_post->ID;
    }else{
      $post_data['post_status'] = $this->load_setting('default_feed_item_status');
      echo llog($post_data['post_status'],"Saving with post status");
    }

    $result = wp_insert_post($post_data,true);

    if($result === false){
      llog($result,"Failed to insert feed item post");
    }else{
      llog($result,"Inserted feed item post");
      $result === true ? $id = $post_data['ID'] : $id = $result;

      // Add terms directly
      $tresult = wp_set_object_terms($id, $tags, 'murms_feed_item_tag');

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

  public static function update_feed_urls(){
    $nodes = self::$wpagg->load_nodes();
    foreach ($nodes as $id => $node) {
      if(!isset($node->data['feed_url']) && isset($node->data['url']){
        $feed_url = self::get_feed_url($node->data['url']);
        if(!$feed_url){
          $feed_url = 'not_found';
        }
        $node->setProperty('feed_url',$feed_url);
        $node->save();
      }
    }
  }


  public static function get_feed_url($node_url){
    if(@file_get_contents($node_url)){
      preg_match_all('/<link\srel\=\"alternate\"\stype\=\"application\/(?:rss|atom)\+xml\"\stitle\=\".*href\=\"(.*)\"\s\/\>/', file_get_contents($url), $matches);
      return $matches[1][0];
    }
    return false;
  }

  public static function update_feeds(){

      self::delete_all_feed_items();

      $feed_items = array();
      //$since_time = $this->env->load_setting('feed_update_time');

      $max_feed_items = $this->settings['max_feed_items'];
      $max_feed_items_per_node = $this->settings['max_feed_items_per_node'];

      // Get the locally stored nodes
      $nodes = $this->env->load_nodes();

      $results = array(
        'nodes_with_feeds' => 0,
        'feed_items_fetched' => 0,
        'feed_items_saved' => 0
      );

      foreach ($nodes as $node) {

        if($node->data['feed_url']){
          $feed_url = $node->data['feed_url'];

          $feed = $this->feedRequest($feed_url);

          // For some reason this comes with an *xml key that misbehaves...
          $feed = array_shift($feed);

          if(is_array($feed)){
            $node_item_count = 0;

            $results['nodes_with_feeds']++;

            /* RSS includes multiple <item> elements. The RSS parser adds a single ['item'],
            with numerically indexed elements for each item from the RSS. But, if there is only one item in the feed, it doesn't do this, and ['item'] is an array of item properties, not items */

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

        $result = self::save_feed_item($item);

        $results['feed_items_saved']++;
        if($results['feed_items_saved'] == $max_feed_items){
          break;
        }
      }

      $this->setNotice("Feeds updated. ".$results['feed_items_fetched']." feed items fetched from ".$results['nodes_with_feeds']." nodes. ".$results['feed_items_saved']." feed items saved.",'success');

    }

  private function feed_request($url,$since_time = null){

    // Get simpleXML of feed
    try {
      $rss = Feed::loadRss($url);
    } catch (\Exception $e) {
      log("Error connecting to feed URL: ".$url,'warning');
      error("Couldn't load feed");
    }

    $ar = xml2array($rss);

    return $ar;
  }

}
?>
