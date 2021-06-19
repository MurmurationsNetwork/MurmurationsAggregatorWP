<div class="murmurations-feed-item <?php echo $data['classes']; ?>" id="<?php echo $data['id']; ?>">

  <h3><?php echo $data['title']; ?></h3>
  <div class="date"><?php echo $data['pubDate']; ?> from <a href="<?php echo $data['node_info']['url']; ?>"><?php echo $data['node_info']['title']; ?></a></div>
  <div class="categories"><?php echo $data['categories']; ?></div>
  <div class="content"><?php echo $data['content']; ?></div>
  <a class="url" href="<?php echo $data['link']; ?>"><?php echo $data['link']; ?></a>

</div>
