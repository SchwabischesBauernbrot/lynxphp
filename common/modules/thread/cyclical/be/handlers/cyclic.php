<?php

$params = $get();

$boardUri = $params['params']['uri'];

$posts_model = getPostsModel($boardUri);
if ($posts_model === false) {
  // this board does not exist
  sendResponse2(array(), array('code' => 404, 'err'  => 'Board not found'));
  return;
}
$threadNum = $params['params']['threadNum'];

global $db;
$row = $db->findById($posts_model, $threadNum);
$data = json_decode($row, true);
// make cyclical
$data['cyclic'] = true;
$db->updateById($posts_model, $threadNum, array('json' => json_encode($data)));

sendResponse2(array(
  'success' => 'ok',
));