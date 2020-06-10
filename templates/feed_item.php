<div class="murmurations-feed-item <?= $data['classes'] ?>" id="<?= $data['id'] ?>">

  <h3><?= $data['title'] ?></h3>
  <div class="date"><?= $data['pubDate'] ?> from <a href="<?= $data['node_info']['url'] ?>"><?= $data['node_info']['title'] ?></a></div>
  <div class="categories"><?= $data['categories'] ?></div>
  <div class="content"><?= $data['content'] ?></div>
  <a class="url" href="<?= $data['link'] ?>"><?= $data['link'] ?></a>

</div>
