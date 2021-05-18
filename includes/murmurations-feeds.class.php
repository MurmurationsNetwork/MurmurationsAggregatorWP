<?php

class Murmurations_Feeds {

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
