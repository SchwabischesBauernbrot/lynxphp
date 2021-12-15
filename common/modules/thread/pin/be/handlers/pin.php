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

// pin
global $db;
$db->updateById($posts_model, $threadNum, array('sticky' => true));

sendResponse2(array(
  'success' => 'ok',
));