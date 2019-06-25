<div class="murmurations-node <?= $data_classes ?>" id="<?= $node->murmurations['url'] ?>">
  <h3 class="murmurations-node-name"><?= $node->murmurations['name'] ?></h3>
  <div class="murmurations-node-tagline"><?= $node->murmurations['tagline'] ?></div>
  <div class="murmurations-node-image"><img src="<?= $node->murmurations['logo'] ?>"></div>
  <div class="murmurations-node-mission"><?= $node->murmurations['mission'] ?></div>
  <div class="murmurations-node-org-types"><?= $node->murmurations['nodeTypes'] ?></div>
  <a class="murmurations-node-url" href="<?= $node->murmurations['url'] ?>"><?= $node->murmurations['url'] ?></a>
</div>
