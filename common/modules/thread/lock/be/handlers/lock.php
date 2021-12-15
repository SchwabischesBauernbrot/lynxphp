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
/*
$row = $db->findById($posts_model, $threadNum);
if ($db->isTrue($row['deleted'])) return sendResponse2(array(), array('code' => 500, 'err'  => 'Thread has been deleted'));
*/
// closed
$db->updateById($posts_model, $threadNum, array('closed' => true));

sendResponse2(array(
  'success' => 'ok',
));